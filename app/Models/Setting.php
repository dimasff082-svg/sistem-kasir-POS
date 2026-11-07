<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    /**
     * Cache memori lokal agar query setting tidak berulang pada satu siklus request.
     *
     * @var array<string, mixed>|null
     */
    protected static ?array $cachedSettings = null;

    /**
     * Nilai standar bawaan jika setting belum tersimpan di database.
     *
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        'store_name' => 'Fira Cake Bakery & Pastry',
        'store_phone' => '082131117996',
        'store_email' => 'info@firacake.com',
        'store_address' => 'BISOLPIN, Ruko Ruby, Jl. Boulevard Rubi II, Masale, Panakkukang, Makassar',
        'store_hours' => '08:00 - 21:00 WITA',
        'online_welcome_title' => 'Selamat Datang di Fira Cake!',
        'online_welcome_description' => 'Nikmati aneka pilihan roti, bolu fresh, kue tart, dan pastry premium kualitas terbaik kami langsung dibuat segar setiap hari.',
        'attendance_max_distance' => '15',
        'app_logo' => null,
    ];

    /**
     * Mengosongkan cache memori settings.
     */
    public static function clearCache(): void
    {
        static::$cachedSettings = null;
    }

    /**
     * Mengambil batas maksimal radius absensi karyawan dalam satuan meter.
     */
    public static function getAttendanceMaxDistance(): float
    {
        $val = static::get('attendance_max_distance', 15.0);

        return is_numeric($val) && (float) $val > 0 ? (float) $val : 15.0;
    }

    /**
     * Mengambil nama toko yang telah dikonfigurasi.
     */
    public static function getStoreName(): string
    {
        return (string) static::get('store_name', self::DEFAULTS['store_name']);
    }

    /**
     * Memformat nama toko menjadi dua baris.
     * Jika admin sudah menyertakan baris baru (\n), gunakan baris tersebut.
     * Jika belum, pisahkan secara cerdas pada pemisah kata atau simbol.
     */
    public static function getStoreNameFormattedTwoLines(): string
    {
        $name = trim(static::getStoreName());

        if (str_contains($name, "\n")) {
            return $name;
        }

        if (str_contains($name, ' - ')) {
            return str_replace(' - ', "\n", $name);
        }

        // Jika diawali "Fira Cake " dan ada kata lanjutan (misal "Fira Cake Bakery & Pastry")
        if (str_starts_with(strtolower($name), 'fira cake ') && strlen($name) > 10) {
            return substr($name, 0, 9)."\n".trim(substr($name, 9));
        }

        // Jika ada 3 kata atau lebih, pisahkan seimbang di tengah
        $words = preg_split('/\s+/', $name);
        if ($words !== false && count($words) >= 4) {
            $mid = (int) ceil(count($words) / 2);

            return implode(' ', array_slice($words, 0, $mid))."\n".implode(' ', array_slice($words, $mid));
        }
        if ($words !== false && count($words) === 3) {
            return $words[0].' '.$words[1]."\n".$words[2];
        }
        if ($words !== false && count($words) === 2) {
            return $words[0]."\n".$words[1];
        }

        return $name;
    }

    /**
     * Mengambil nomor kontak toko yang telah dikonfigurasi.
     */
    public static function getStorePhone(): string
    {
        return (string) static::get('store_phone', self::DEFAULTS['store_phone']);
    }

    /**
     * Mengambil alamat lengkap toko yang telah dikonfigurasi.
     */
    public static function getStoreAddress(): string
    {
        return (string) static::get('store_address', self::DEFAULTS['store_address']);
    }

    /**
     * Mengambil email resmi toko yang telah dikonfigurasi.
     */
    public static function getStoreEmail(): string
    {
        return (string) static::get('store_email', self::DEFAULTS['store_email']);
    }

    /**
     * Mengambil jam operasional toko yang telah dikonfigurasi.
     */
    public static function getStoreHours(): string
    {
        return (string) static::get('store_hours', self::DEFAULTS['store_hours']);
    }

    /**
     * Mengambil URL aset gambar logo toko.
     * Mengutamakan file yang diunggah ke storage, atau default fallback ke logo aset bawaan.
     */
    public static function getLogoUrl(): string
    {
        $logo = static::get('app_logo');

        if (! empty($logo)) {
            if (str_starts_with((string) $logo, 'http://') || str_starts_with((string) $logo, 'https://')) {
                return (string) $logo;
            }

            return asset('storage/'.$logo);
        }

        return asset('images/logo.png');
    }

    /**
     * Mengambil nilai pengaturan berdasarkan key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = static::allMapped();

        if (array_key_exists($key, $settings) && $settings[$key] !== null && $settings[$key] !== '') {
            return $settings[$key];
        }

        if ($default !== null) {
            return $default;
        }

        return self::DEFAULTS[$key] ?? null;
    }

    /**
     * Menyimpan atau memperbarui pengaturan.
     */
    public static function set(string $key, mixed $value, string $type = 'text', string $group = 'general'): self
    {
        static::clearCache();

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
            ]
        );
    }

    /**
     * Booted model event untuk memastikan cache selalu sinkron.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });
        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Mengambil seluruh pengaturan sebagai associative array dengan default fallback.
     *
     * @return array<string, mixed>
     */
    public static function allMapped(): array
    {
        if (app()->runningUnitTests()) {
            try {
                $dbSettings = static::pluck('value', 'key')->toArray();

                return array_merge(self::DEFAULTS, array_filter($dbSettings, fn ($v) => $v !== null && $v !== ''));
            } catch (\Throwable) {
                return self::DEFAULTS;
            }
        }

        if (static::$cachedSettings !== null) {
            return static::$cachedSettings;
        }

        try {
            $dbSettings = static::pluck('value', 'key')->toArray();
            static::$cachedSettings = array_merge(self::DEFAULTS, array_filter($dbSettings, fn ($v) => $v !== null && $v !== ''));
        } catch (\Throwable) {
            static::$cachedSettings = self::DEFAULTS;
        }

        return static::$cachedSettings;
    }
}
