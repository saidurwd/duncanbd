<?php

namespace App\Domain\Documents\Policies;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Identity\Models\User;

/** Investor/statutory categories need documents.investor.manage; the rest need documents.general.manage. */
class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documents.investor.manage') || $user->can('documents.general.manage');
    }

    public function manageCategory(User $user, DocumentCategory $category): bool
    {
        return $user->can($category->is_investor ? 'documents.investor.manage' : 'documents.general.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->manageCategory($user, $document->category);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
