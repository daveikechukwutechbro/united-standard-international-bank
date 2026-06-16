<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Enums;

enum AnomalyType: string
{
    case Statistical = 'statistical';
    case Behavioral = 'behavioral';
    case Velocity = 'velocity';
    case Geolocation = 'geolocation';

    public function label(): string
    {
        return match ($this) {
            self::Statistical => 'Statistical Anomaly',
            self::Behavioral  => 'Behavioral Anomaly',
            self::Velocity    => 'Velocity Anomaly',
            self::Geolocation => 'Geolocation Anomaly',
        };
    }
}
