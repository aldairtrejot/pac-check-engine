<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdscripcionCatalogs
{
    public static function search(?string $search, int $limit = 100): Collection
    {
        if (! self::tableExists()) {
            return collect();
        }

        $search = self::norm($search);
        $limit = max(1, min($limit, 200));

        $query = self::baseQuery();

        if ($search !== '') {
            $terms = collect(preg_split('/\s+/', $search) ?: [])
                ->map(fn ($term) => trim((string) $term))
                ->filter(fn ($term) => $term !== '')
                ->take(6)
                ->values();

            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $query->where(function ($query) use ($like) {
                    $query->whereRaw('ca.id_adscripcion::text LIKE ?', [$like])
                        ->orWhereRaw(
                            "UPPER(public.unaccent(COALESCE(ca.adscripcion, ''))) LIKE UPPER(public.unaccent(?))",
                            [$like]
                        )
                        ->orWhereRaw(
                            "UPPER(public.unaccent(COALESCE(ca.adscripcion_compl, ''))) LIKE UPPER(public.unaccent(?))",
                            [$like]
                        )
                        ->orWhereRaw(
                            "UPPER(public.unaccent(COALESCE(ca.nombre_unidad, ''))) LIKE UPPER(public.unaccent(?))",
                            [$like]
                        )
                        ->orWhereRaw(
                            "UPPER(public.unaccent(COALESCE(ca.nombre_coordinacion, ''))) LIKE UPPER(public.unaccent(?))",
                            [$like]
                        );
                });
            }
        }

        return $query
            ->orderBy('adscripcion_compl')
            ->orderBy('adscripcion')
            ->orderBy('ca.id_adscripcion')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => self::format($row));
    }

    public static function findById($idAdscripcion): ?object
    {
        if (! self::tableExists()) {
            return null;
        }

        $idAdscripcion = (int) $idAdscripcion;

        if ($idAdscripcion <= 0) {
            return null;
        }

        $row = self::baseQuery()
            ->where('ca.id_adscripcion', $idAdscripcion)
            ->orderBy('adscripcion_compl')
            ->orderBy('adscripcion')
            ->first();

        return $row ? self::format($row) : null;
    }

    public static function findByUnidadCoordinacion($idUnidad, $idCoordinacion): ?object
    {
        if (! self::tableExists()) {
            return null;
        }

        $idUnidad = (int) $idUnidad;
        $idCoordinacion = (int) $idCoordinacion;

        if ($idUnidad <= 0 || $idCoordinacion <= 0) {
            return null;
        }

        $row = self::baseQuery()
            ->where('ca.id_unidad', $idUnidad)
            ->where('ca.id_coordinacion', $idCoordinacion)
            ->orderBy('adscripcion_compl')
            ->orderBy('adscripcion')
            ->first();

        return $row ? self::format($row) : null;
    }

    public static function norm($value): string
    {
        return mb_strtoupper(trim((string) $value), 'UTF-8');
    }

    private static function baseQuery()
    {
        return DB::table('public.cat_adscripcion as ca')
            ->selectRaw("
                ca.id_unidad,
                UPPER(BTRIM(COALESCE(ca.nombre_unidad, ''))) as nombre_unidad,
                ca.id_coordinacion,
                UPPER(BTRIM(COALESCE(ca.nombre_coordinacion, ''))) as nombre_coordinacion,
                ca.id_adscripcion,
                UPPER(BTRIM(COALESCE(ca.adscripcion, ''))) as adscripcion,
                UPPER(BTRIM(COALESCE(ca.adscripcion_compl, ''))) as adscripcion_compl
            ")
            ->whereNotNull('ca.id_adscripcion')
            ->distinct();
    }

    private static function format(object $row): object
    {
        $row->id_unidad = $row->id_unidad !== null ? (int) $row->id_unidad : null;
        $row->id_coordinacion = $row->id_coordinacion !== null ? (int) $row->id_coordinacion : null;
        $row->id_adscripcion = (int) $row->id_adscripcion;
        $row->nombre_unidad = self::norm($row->nombre_unidad ?? '');
        $row->nombre_coordinacion = self::norm($row->nombre_coordinacion ?? '');
        $row->adscripcion = self::norm($row->adscripcion ?? '');
        $row->adscripcion_compl = self::norm($row->adscripcion_compl ?? '');
        $row->label = $row->adscripcion_compl !== ''
            ? $row->adscripcion_compl
            : $row->adscripcion;
        $row->descripcion = $row->label;
        $row->id = $row->id_adscripcion;

        return $row;
    }

    private static function tableExists(): bool
    {
        try {
            return DB::table('information_schema.tables')
                ->where('table_schema', 'public')
                ->where('table_name', 'cat_adscripcion')
                ->exists();
        } catch (\Throwable $th) {
            return false;
        }
    }
}
