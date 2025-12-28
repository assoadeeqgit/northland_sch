<?php
/**
 * TimetableHelper.php
 * Handles timetable period generation, time rules, and dummy subject logic.
 */

class TimetableHelper {
    public static function getRules($class_level, $class_name = '') {
        // SS vs JSS differentiation
        $is_ss = (stripos($class_name, 'SS') === 0);
        $is_jss = (stripos($class_name, 'JSS') === 0);

        if ($is_ss || $is_jss || $class_level === 'Secondary') {
            return [
                'type' => 'Secondary',
                'start_time' => '08:00',
                'end_time' => '13:40',
                'period_duration' => 40,
                'total_periods' => 8,
                'break_start' => '10:40',
                'break_end' => '11:00',
                'break_after_period' => 4
            ];
        } elseif ($class_level === 'Primary') {
            return [
                'type' => 'Primary',
                'start_time' => '08:00',
                'end_time' => '13:30',
                'period_duration' => 30,
                'total_periods' => 10,
                'break_start' => '10:00',
                'break_end' => '10:30',
                'break_after_period' => 4
            ];
        } elseif ($class_level === 'Early Childhood') {
            return [
                'type' => 'Early Childhood',
                'start_time' => '08:00',
                'end_time' => '13:30',
                'period_duration' => 30,
                'total_periods' => 10,
                'break_start' => '09:30',
                'break_end' => '10:00',
                'break_after_period' => 3
            ];
        }

        // Default to Primary if unknown
        return self::getRules('Primary');
    }

    public static function generatePeriods($rules) {
        $periods = [];
        $current_time = strtotime($rules['start_time']);
        
        $breakAdded = false;
        for ($i = 1; $i <= $rules['total_periods']; $i++) {
            // Check if break should occur before this period
            if ($i > $rules['break_after_period'] && !$breakAdded) {
                $periods[] = [
                    'name' => 'Break',
                    'start' => $rules['break_start'],
                    'end' => $rules['break_end'],
                    'is_break' => true
                ];
                $current_time = strtotime($rules['break_end']);
                $breakAdded = true;
            }

            $start = date('H:i', $current_time);
            $end = date('H:i', strtotime("+{$rules['period_duration']} minutes", $current_time));
            
            $periods[] = [
                'name' => "Period $i",
                'start' => $start,
                'end' => $end,
                'is_break' => false
            ];
            
            $current_time = strtotime($end);
        }
        
        return $periods;
    }

    public static function getDummySubjects($conn) {
        $stmt = $conn->query("SELECT id, subject_name FROM subjects WHERE is_dummy = 1 AND is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getRandomDummySubjectId($conn) {
        $stmt = $conn->query("SELECT id FROM subjects WHERE is_dummy = 1 AND is_active = 1 ORDER BY RAND() LIMIT 1");
        return $stmt->fetchColumn();
    }
}
