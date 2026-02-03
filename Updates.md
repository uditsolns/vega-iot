
```php
enum ValidationQualificationType: string
{
case IQ = 'IQ';
case OQ = 'OQ';
case PQ = 'PQ';

    /**
     * Get display label for qualification type
     */
    public function label(): string
    {
        return match ($this) {
            self::IQ => 'Installation Qualification (IQ)',
            self::OQ => 'Operational Qualification (OQ)',
            self::PQ => 'Performance Qualification (PQ)',
        };
    }
}
```
