<?php

namespace App\Observers;

use App\Models\Accounting\DocumentLineItem;

class DocumentLineItemObserver
{
    /**
     * Handle the DocumentLineItem "created" event.
     */
    public function created(DocumentLineItem $documentLineItem): void
    {
        //
    }

    /**
     * Handle the DocumentLineItem "updated" event.
     */
    public function updated(DocumentLineItem $documentLineItem): void
    {
        //
    }

    /**
     * Handle the DocumentLineItem "deleted" event.
     */
    public function deleted(DocumentLineItem $documentLineItem): void
    {
        $documentLineItem->adjustments()->detach();
    }

    /**
     * Handle the DocumentLineItem "restored" event.
     */
    public function restored(DocumentLineItem $documentLineItem): void
    {
        //
    }

    /**
     * Handle the DocumentLineItem "force deleted" event.
     */
    public function forceDeleted(DocumentLineItem $documentLineItem): void
    {
        //
    }
}
