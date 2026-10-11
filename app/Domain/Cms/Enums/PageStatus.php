<?php

namespace App\Domain\Cms\Enums;

enum PageStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::InReview => 'In review',
            default => ucfirst($this->value),
        };
    }

    /**
     * Workflow edges (architecture doc §6.4) mapped to the permission needed to take them.
     *
     * @return array<string, string> target status => permission
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::InReview->value => 'pages.edit'],
            self::InReview => [
                self::Approved->value => 'pages.publish',
                self::Draft->value => 'pages.publish', // changes requested
            ],
            self::Approved => [
                self::Published->value => 'pages.publish',
                self::Scheduled->value => 'pages.publish',
                self::Draft->value => 'pages.publish',
            ],
            self::Scheduled => [self::Draft->value => 'pages.publish', self::Published->value => 'pages.publish'],
            self::Published => [self::Archived->value => 'pages.publish', self::Draft->value => 'pages.publish'],
            self::Archived => [self::Draft->value => 'pages.publish'],
        };
    }
}
