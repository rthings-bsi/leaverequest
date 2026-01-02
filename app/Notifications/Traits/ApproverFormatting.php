<?php

namespace App\Notifications\Traits;

trait ApproverFormatting
{
    /**
     * Strip common role words from a name if they were mistakenly included.
     * e.g. "Manager John Doe" -> "John Doe"; "Manager" -> null
     *
     * @param  string|null  $name
     * @return string|null
     */
    protected function stripApproverName(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        // Remove common role words (case-insensitive). Keep the rest.
        $clean = preg_replace('/\b(manager|supervisor|hod|hrd)\b/iu', '', $name);
        // Normalize whitespace
        $clean = trim(preg_replace('/\s+/u', ' ', (string) $clean));

        return $clean === '' ? null : $clean;
    }

    /**
     * Build the "by" text for supervisor/manager combinations.
     * Returns strings like: "by Supervisor Alice and Manager Bob" or "by Manager Bob".
     *
     * @param  string|null  $supervisor
     * @param  string|null  $manager
     * @return string
     */
    protected function formatByText(?string $supervisor, ?string $manager): string
    {
        $s = $this->stripApproverName($supervisor);
        $m = $this->stripApproverName($manager);

        if ($s && $m) {
            return 'by Supervisor ' . $s . ' and Manager ' . $m;
        }

        if ($m) {
            return 'by Manager ' . $m;
        }

        if ($s) {
            return 'by Supervisor ' . $s;
        }

        return 'by the appropriate authority';
    }
}
