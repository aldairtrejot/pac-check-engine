<?php

namespace App\Http\Controllers\Helpers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use HTMLPurifier;
use HTMLPurifier_Config;
class TemplateTableController extends Controller
{
    /**
     * The function sanitizes the input data of the table
     * @param \Illuminate\Http\Request $request
     * @return array{limit: mixed, offset: mixed, search: string, select: mixed}
     */
    public function validateAndSanitizePagination(Request $request): array
    {
        // Validate the input fields
        $validated = $request->validate([
            'limit' => 'required|integer|min:5|max:100', // Limit must be between 5 and 100
            'offset' => 'required|integer|min:0',        // Offset must be 0 or more
            'search' => 'nullable|string|max:50',        // Search is optional, max 50 characters
            'select' => 'required|integer|min:4|max:51', // Select must be between 4 and 51
        ]);

        // Sanitize the search field
        $config = HTMLPurifier_Config::createDefault(); // Create default config for HTMLPurifier
        $purifier = new HTMLPurifier($config);          // Create a purifier instance
        $search = $purifier->purify($validated['search'] ?? ''); // Clean the search input (if present)

        return [
            'limit' => $validated['limit'],   // Return validated limit
            'offset' => $validated['offset'], // Return validated offset
            'select' => $validated['select'], // Return validated select
            'search' => $search,              // Return sanitized search input
        ];
    }
}
