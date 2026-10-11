<tr>
    <td data-num>{{ is_numeric($i) ? $i + 1 : '' }}</td>
    <td><label class="visually-hidden" for="mi-label-{{ $i }}">Label</label><input id="mi-label-{{ $i }}" name="items[{{ $i }}][label]" value="{{ $row['label'] ?? '' }}" class="form-control form-control-sm" required></td>
    <td>
        <label class="visually-hidden" for="mi-page-{{ $i }}">Page</label>
        <select id="mi-page-{{ $i }}" name="items[{{ $i }}][page_id]" class="form-select form-select-sm">
            <option value="">—</option>
            @foreach ($pages as $page)<option value="{{ $page->id }}" @selected((int) ($row['page_id'] ?? 0) === $page->id)>/{{ $page->path }}</option>@endforeach
        </select>
    </td>
    <td><label class="visually-hidden" for="mi-url-{{ $i }}">URL</label><input id="mi-url-{{ $i }}" name="items[{{ $i }}][url]" value="{{ $row['url'] ?? '' }}" class="form-control form-control-sm" placeholder="https://… or /path"></td>
    <td><label class="visually-hidden" for="mi-parent-{{ $i }}">Parent number</label><input id="mi-parent-{{ $i }}" type="number" min="1" name="items[{{ $i }}][parent]" value="{{ isset($row['parent']) ? $row['parent'] + 1 : '' }}" class="form-control form-control-sm" data-parent></td>
    <td><input type="checkbox" name="items[{{ $i }}][target]" value="_blank" aria-label="Open in new tab" @checked(($row['target'] ?? '') === '_blank')></td>
    <td><input type="hidden" name="items[{{ $i }}][is_active]" value="0"><input type="checkbox" name="items[{{ $i }}][is_active]" value="1" aria-label="Active" @checked($row['is_active'] ?? true)></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row>Remove</button></td>
</tr>
