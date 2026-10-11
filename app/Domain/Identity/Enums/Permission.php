<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case UsersManage = 'users.manage';
    case SystemManage = 'system.manage';
    case SettingsManage = 'settings.manage';
    case MenusManage = 'menus.manage';
    case SeoManage = 'seo.manage';
    case PagesPublish = 'pages.publish';
    case PagesEdit = 'pages.edit';
    case NewsEdit = 'news.edit';
    case DocumentsInvestorManage = 'documents.investor.manage';
    case DocumentsGeneralManage = 'documents.general.manage';
    case MediaManage = 'media.manage';
    case MediaView = 'media.view';
    case CareersManage = 'careers.manage';
    case EnquiriesManage = 'enquiries.manage';
    case EnquiriesView = 'enquiries.view';
    case AuditView = 'audit.view';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
