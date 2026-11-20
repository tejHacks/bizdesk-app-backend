<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['category', 'amount', 'note', 'spent_on'])]
class Expense extends Model
{
    public const CATEGORIES = ['Rent', 'Stock purchase', 'Salaries', 'Utilities', 'Transport', 'Marketing', 'Other'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'spent_on' => 'date'];
    }
}
