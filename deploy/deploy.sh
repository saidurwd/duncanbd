#!/usr/bin/env bash
# Zero-downtime release deploy with one-command rollback (release folders + atomic symlink switch).
#
#   DEPLOY_PATH=/var/www/duncanbd REPO_URL=git@github.com:org/duncanbd.git deploy/deploy.sh deploy v1.0.0
#   DEPLOY_PATH=/var/www/duncanbd deploy/deploy.sh rollback
#   DEPLOY_PATH=/var/www/duncanbd deploy/deploy.sh status
#
# Layout under $DEPLOY_PATH:  releases/<timestamp>/   current -> releases/<timestamp>   shared/{.env,storage}
# Nginx root is $DEPLOY_PATH/current/public. Database migrations MUST be backward compatible for one release,
# because a rollback switches the code back but does not undo migrations.
set -euo pipefail

: "${DEPLOY_PATH:?set DEPLOY_PATH}"
KEEP="${KEEP:-5}"
PHP="${PHP_BIN:-php}"
RELOAD_CMD="${RELOAD_CMD:-sudo systemctl reload php8.4-fpm}"   # set RELOAD_CMD=true to skip (e.g. local drills)
SKIP_BUILD="${SKIP_BUILD:-0}"                                    # 1 = assets already built (artifact upload)

releases="$DEPLOY_PATH/releases"; shared="$DEPLOY_PATH/shared"; current="$DEPLOY_PATH/current"
log() { printf '\033[1m==> %s\033[0m\n' "$*"; }

switch_to() {   # atomic: create the new link beside the old one, then rename over it
  ln -sfn "$1" "$DEPLOY_PATH/.current_next"
  mv -Tf "$DEPLOY_PATH/.current_next" "$current" 2>/dev/null || { rm -f "$current"; mv "$DEPLOY_PATH/.current_next" "$current"; }
}

post_switch() {
  (cd "$current" && "$PHP" artisan queue:restart >/dev/null 2>&1 || true)
  eval "$RELOAD_CMD" || true   # clears OPcache so the new code is served
}

cmd_deploy() {
  local ref="${1:?usage: deploy <git-ref>}" id new
  id="$(date +%Y%m%d%H%M%S)"; new="$releases/$id"
  mkdir -p "$releases" "$shared/storage"/{app/public,app/private,framework/{cache,sessions,views},logs}
  [ -f "$shared/.env" ] || { echo "Missing $shared/.env (create it once, chmod 600)"; exit 1; }

  log "Fetching $ref"
  git clone --quiet --depth 1 --branch "$ref" "${REPO_URL:?set REPO_URL}" "$new" 2>/dev/null || git clone --quiet "$REPO_URL" "$new" && git -C "$new" checkout --quiet "$ref"
  rm -rf "$new/.git"

  log "Installing dependencies"
  (cd "$new" && composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction --prefer-dist >/dev/null)
  if [ "$SKIP_BUILD" != "1" ]; then (cd "$new" && npm ci --silent && npm run build >/dev/null && rm -rf node_modules); fi

  log "Linking shared files"
  rm -rf "$new/storage"; ln -s "$shared/storage" "$new/storage"
  ln -s "$shared/.env" "$new/.env"

  log "Migrating and warming caches"
  (cd "$new" && "$PHP" artisan migrate --force --no-interaction && "$PHP" artisan storage:link --force >/dev/null \
      && "$PHP" artisan optimize >/dev/null && "$PHP" artisan view:cache >/dev/null)

  local previous=""; [ -L "$current" ] && previous="$(readlink "$current")"
  log "Switching traffic to $id"
  switch_to "$new"; post_switch

  log "Smoke test"
  if ! (cd "$current" && "$PHP" artisan about --only=environment >/dev/null 2>&1); then
    echo "Smoke test failed — rolling back"; [ -n "$previous" ] && { switch_to "$previous"; post_switch; }; exit 1
  fi

  ls -1dt "$releases"/*/ | tail -n +$((KEEP + 1)) | xargs -r rm -rf   # prune old releases
  if [ -n "$previous" ]; then log "Deployed $ref as $id (previous: $(basename "$previous"))"; else log "Deployed $ref as $id (first release)"; fi
}

cmd_rollback() {
  local now prev
  now="$(readlink "$current" | xargs basename)"
  prev="$(ls -1dt "$releases"/*/ | xargs -n1 basename | grep -vx "$now" | head -1 || true)"
  [ -n "$prev" ] || { echo "No previous release to roll back to"; exit 1; }
  log "Rolling back $now -> $prev"
  switch_to "$releases/$prev"; post_switch
  log "Now serving $prev"
}

cmd_status() {
  echo "current: $(readlink "$current" 2>/dev/null || echo none)"
  echo "releases:"; ls -1t "$releases" 2>/dev/null | sed 's/^/  /'
}

case "${1:-}" in
  deploy) shift; cmd_deploy "$@" ;;
  rollback) cmd_rollback ;;
  status) cmd_status ;;
  *) echo "usage: $0 {deploy <ref>|rollback|status}"; exit 1 ;;
esac
