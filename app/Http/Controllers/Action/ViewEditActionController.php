<?php

namespace App\Http\Controllers\Action;

use App\Http\Controllers\Controller;

class ViewEditActionController extends Controller
{
    /**
     * The function returns the web view edit
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(string $id)
    {
        try {
            // Check if the ID is numeric (or matches your expected format)
            if (! preg_match('/^\d+$/', $id)) {
                abort(404); // Or redirect to an error page
            }
            // Find the record using Eloquent
            $result = EntityTeacherModel::find($id);

            // If the record doesn't exist, abort with 404
            if (! $result) {
                abort(404); // Or redirect if it doesn't exist
            }

            // Return the form view with the record data
            return view('action.form', compact('id'));
        } catch (\Exception $e) {
            abort(404);  // Return a 404 error in production if any exception occurs
        }
    }
}
