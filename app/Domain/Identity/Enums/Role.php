<?php

namespace App\Domain\Identity\Enums;

enum Role: string
{
    case SuperAdmin = 'super-admin';
    case SiteAdmin = 'site-admin';
    case Editor = 'editor';
    case Author = 'author';
    case InvestorRelations = 'investor-relations';
    case Hr = 'hr';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::SiteAdmin => 'Site Admin',
            self::Editor => 'Editor',
            self::Author => 'Author',
            self::InvestorRelations => 'IR / Company Secretary',
            self::Hr => 'HR',
            self::Viewer => 'Viewer',
        };
    }

    /**
     * Permissions granted to the role (architecture doc §8.2). Super Admin gets everything.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SuperAdmin => Permission::values(),
            self::SiteAdmin => [
                'settings.manage', 'menus.manage', 'seo.manage',
                'pages.publish', 'pages.edit', 'news.edit',
                'documents.investor.manage', 'documents.general.manage',
                'media.manage', 'careers.manage', 'enquiries.manage', 'audit.view',
            ],
            self::Editor => [
                'pages.publish', 'pages.edit', 'news.edit',
                'documents.general.manage', 'media.manage', 'enquiries.manage',
            ],
            self::Author => ['pages.edit', 'news.edit', 'media.manage'],
            self::InvestorRelations => [
                'documents.investor.manage', 'documents.general.manage',
                'media.manage', 'enquiries.manage',
            ],
            self::Hr => ['careers.manage', 'media.manage', 'enquiries.manage'],
            self::Viewer => ['media.view', 'enquiries.view'],
        };
    }
}
