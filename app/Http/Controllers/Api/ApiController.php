<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    /**
     * Tenant isolation: a record that belongs to another business is reported as
     * "not found" (404), so its existence isn't even revealed.
     */
    protected function owned(Request $request, Model $model): Model
    {
        abort_unless((int) $model->getAttribute('user_id') === (int) $request->user()->id, 404);

        return $model;
    }
}
