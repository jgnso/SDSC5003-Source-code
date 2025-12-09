<?php
if (!function_exists('ensure_participation_constraints')) {
    function ensure_participation_constraints(SQLite3 $db): void
    {
        $db->exec("UPDATE Participation SET Medal = 'None' WHERE Medal IS NULL OR TRIM(Medal) = ''");
        $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_participation_unique ON Participation (AthleteID, EventID, Medal)');
    }
}
