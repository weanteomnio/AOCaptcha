<?php

namespace weanteomnio\AOCaptcha;

class BehaviorAnalyzer
{
    /**
     * @param array<int, array{x:float,y:float,t:int}> $movements
     */
    public function looksHuman(array $movements, float $duration): bool
    {
        $n = count($movements);

        if ($n < 4) {
            return false;
        }
        if ($duration < 80) {
            return false;
        }
        if ($duration > 30000) {
            return false;
        }

        if ($n >= 6) {
            $first = $movements[0];
            $last = $movements[$n - 1];
            $directDist = sqrt(
                ((float) ($last['x'] ?? 0) - (float) ($first['x'] ?? 0)) ** 2 +
                ((float) ($last['y'] ?? 0) - (float) ($first['y'] ?? 0)) ** 2
            );
            $pathDist = 0.0;
            for ($i = 1; $i < $n; $i++) {
                $pathDist += sqrt(
                    ((float) ($movements[$i]['x'] ?? 0) - (float) ($movements[$i - 1]['x'] ?? 0)) ** 2 +
                    ((float) ($movements[$i]['y'] ?? 0) - (float) ($movements[$i - 1]['y'] ?? 0)) ** 2
                );
            }
            if ($directDist > 0.1 && ($pathDist / $directDist) < 1.015) {
                return false;
            }
        }

        $score = 0;

        if ($duration >= 200 && $duration <= 8000) {
            $score += 3;
        } elseif ($duration >= 100 && $duration <= 15000) {
            $score += 1;
        }

        $pps = $n / max($duration / 1000, 0.01);
        if ($pps >= 4 && $pps <= 120) {
            $score += 2;
        } elseif ($pps >= 2) {
            $score += 1;
        }

        $speeds = [];
        for ($i = 1; $i < $n; $i++) {
            $dx = (float) (($movements[$i]['x'] ?? 0) - ($movements[$i - 1]['x'] ?? 0));
            $dy = (float) (($movements[$i]['y'] ?? 0) - ($movements[$i - 1]['y'] ?? 0));
            $dt = max((float) (($movements[$i]['t'] ?? 0) - ($movements[$i - 1]['t'] ?? 0)), 1.0);
            $speeds[] = sqrt($dx * $dx + $dy * $dy) / $dt;
        }
        if (count($speeds) >= 2) {
            $avg = array_sum($speeds) / count($speeds);
            $var = 0.0;
            foreach ($speeds as $s) {
                $var += ($s - $avg) ** 2;
            }
            $var /= count($speeds);
            $cv = $avg > 0.001 ? sqrt($var) / $avg : 0;

            if ($cv > 0.25) {
                $score += 3;
            } elseif ($cv > 0.10) {
                $score += 2;
            } elseif ($cv > 0.03) {
                $score += 1;
            }
        }

        if ($n >= 3) {
            $first = $movements[0];
            $last = $movements[$n - 1];
            $directDist = sqrt(
                ((float) ($last['x'] ?? 0) - (float) ($first['x'] ?? 0)) ** 2 +
                ((float) ($last['y'] ?? 0) - (float) ($first['y'] ?? 0)) ** 2
            );
            $pathDist = 0.0;
            for ($i = 1; $i < $n; $i++) {
                $pathDist += sqrt(
                    ((float) ($movements[$i]['x'] ?? 0) - (float) ($movements[$i - 1]['x'] ?? 0)) ** 2 +
                    ((float) ($movements[$i]['y'] ?? 0) - (float) ($movements[$i - 1]['y'] ?? 0)) ** 2
                );
            }
            $ratio = $directDist > 0.1 ? $pathDist / $directDist : 1.0;

            if ($ratio > 1.08) {
                $score += 3;
            } elseif ($ratio > 1.02) {
                $score += 2;
            } elseif ($ratio > 1.0) {
                $score += 1;
            }
        }

        $tail = array_slice($movements, max(0, (int) ($n * 0.6)));
        $changes = 0;
        for ($i = 2, $m = count($tail); $i < $m; $i++) {
            $dx1 = (float) (($tail[$i - 1]['x'] ?? 0) - ($tail[$i - 2]['x'] ?? 0));
            $dx2 = (float) (($tail[$i]['x'] ?? 0) - ($tail[$i - 1]['x'] ?? 0));
            $dy1 = (float) (($tail[$i - 1]['y'] ?? 0) - ($tail[$i - 2]['y'] ?? 0));
            $dy2 = (float) (($tail[$i]['y'] ?? 0) - ($tail[$i - 1]['y'] ?? 0));
            if ($dx1 * $dx2 < -0.01 || $dy1 * $dy2 < -0.01) {
                $changes++;
            }
        }
        if ($changes >= 2) {
            $score += 2;
        } elseif ($changes >= 1) {
            $score += 1;
        }

        $jitterCount = 0;
        for ($i = 2; $i < $n; $i++) {
            $ax = (float) (($movements[$i]['x'] ?? 0) - 2 * ($movements[$i - 1]['x'] ?? 0) + ($movements[$i - 2]['x'] ?? 0));
            $ay = (float) (($movements[$i]['y'] ?? 0) - 2 * ($movements[$i - 1]['y'] ?? 0) + ($movements[$i - 2]['y'] ?? 0));
            $accel = sqrt($ax * $ax + $ay * $ay);
            if ($accel > 0.1 && $accel < 8.0) {
                $jitterCount++;
            }
        }
        $jitterRatio = $n > 4 ? $jitterCount / ($n - 2) : 0;
        if ($jitterRatio > 0.25) {
            $score += 2;
        } elseif ($jitterRatio > 0.10) {
            $score += 1;
        }

        return $score >= 6;
    }
}
