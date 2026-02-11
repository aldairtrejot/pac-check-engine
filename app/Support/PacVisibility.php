<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class PacVisibility
{
    private static array $columnsCache = [];

    public static function apply(Builder $query, $user, string $capTableQualified = 'public.a2_acciones_capacitacion'): void
    {
        if (! $user) {
            abort(401, 'No autenticado');
        }

        // Centrales ven todo
        if (method_exists($user, 'isCentral') && $user->isCentral()) {
            return;
        }

        // Si no es operativo, no filtramos aquí
        if (method_exists($user, 'isOperative') && ! $user->isOperative()) {
            return;
        }

        // Operativos requieren estos IDs
        if (empty($user->id_entidad)) {
            abort(403, 'Usuario operativo sin id_entidad asignado.');
        }
        if (empty($user->id_tipo_nomina)) {
            abort(403, 'Usuario operativo sin id_tipo_nomina asignado.');
        }

        [$schemaCap, $tableCap] = self::splitQualified($capTableQualified);

        $capEntidadCol = self::firstExistingColumn($schemaCap, $tableCap, ['id_entidad', 'entidad']);
        $capNominaCol  = self::firstExistingColumn($schemaCap, $tableCap, ['id_tipo_nomina', 'nomina']);
        $capCluesCol   = self::firstExistingColumn($schemaCap, $tableCap, ['id_clues', 'clave_clues', 'clues']);

        if (! $capEntidadCol) {
            abort(500, "a2_acciones_capacitacion no tiene columna id_entidad ni entidad.");
        }
        if (! $capNominaCol) {
            abort(500, "a2_acciones_capacitacion no tiene columna id_tipo_nomina ni nomina.");
        }

        $cap = $capTableQualified;

        // ==========================
        // ENTIDAD (a2.entidad es TEXTO en tu caso)
        // ==========================
        if ($capEntidadCol === 'id_entidad') {
            $query->where($cap . '.id_entidad', '=', (int) $user->id_entidad);
        } else {
            $entidadTxt = self::lookupLabel(
                'administracion.cat_entidad',
                'id_entidad',
                (int) $user->id_entidad,
                ['entidad', 'descripcion', 'nombre', 'desc_entidad']
            );

            if (! $entidadTxt) {
                abort(403, 'No se pudo resolver el texto de entidad desde cat_entidad.');
            }

            $query->whereRaw("TRIM(UPPER({$cap}.entidad)) = TRIM(UPPER(?))", [$entidadTxt]);
        }

        // ==========================
        // NÓMINA (a2.nomina es TEXTO en tu caso)
        // ==========================
        if ($capNominaCol === 'id_tipo_nomina') {
            $query->where($cap . '.id_tipo_nomina', '=', (int) $user->id_tipo_nomina);
        } else {
            $nominaTxt = self::lookupLabel(
                'administracion.cat_tipo_nomina',
                'id_tipo_nomina',
                (int) $user->id_tipo_nomina,
                ['tipo_nomina', 'nomina', 'descripcion', 'nombre']
            );

            if (! $nominaTxt) {
                abort(403, 'No se pudo resolver el texto de nómina desde cat_tipo_nomina.');
            }

            $query->whereRaw("TRIM(UPPER({$cap}.nomina)) = TRIM(UPPER(?))", [$nominaTxt]);
        }

        // ==========================
        // CLUES (OPCIONAL) - tú sí tienes clave_clues
        // ==========================
        if (! empty($user->id_clues) && $capCluesCol) {
            $cluesTxt = self::lookupLabel(
                'administracion.cat_clues',
                'id_clues',
                (int) $user->id_clues,
                ['clave_clues', 'clues', 'descripcion', 'nombre']
            );

            if ($cluesTxt) {
                if ($capCluesCol === 'id_clues') {
                    $query->where($cap . '.id_clues', '=', (int) $user->id_clues);
                } elseif ($capCluesCol === 'clave_clues') {
                    $query->whereRaw("TRIM(UPPER({$cap}.clave_clues)) = TRIM(UPPER(?))", [$cluesTxt]);
                } else {
                    $query->whereRaw("TRIM(UPPER({$cap}.clues)) = TRIM(UPPER(?))", [$cluesTxt]);
                }
            }
        }
    }

    // ==========================
    // Helpers internos
    // ==========================
    private static function splitQualified(string $qualified): array
    {
        $qualified = trim($qualified);
        if (str_contains($qualified, '.')) {
            $parts = explode('.', $qualified);
            if (count($parts) === 2) return [$parts[0], $parts[1]];
        }
        return ['public', $qualified];
    }

    private static function columnsFor(string $schema, string $table): array
    {
        $key = $schema . '.' . $table;

        if (! isset(self::$columnsCache[$key])) {
            $rows = DB::table('information_schema.columns')
                ->select('column_name')
                ->where('table_schema', $schema)
                ->where('table_name', $table)
                ->get();

            self::$columnsCache[$key] = $rows->pluck('column_name')->map(fn ($c) => (string) $c)->all();
        }

        return self::$columnsCache[$key];
    }

    private static function firstExistingColumn(string $schema, string $table, array $candidates): ?string
    {
        $cols = self::columnsFor($schema, $table);
        $set  = array_flip($cols);

        foreach ($candidates as $c) {
            if (isset($set[$c])) return $c;
        }
        return null;
    }

    private static function lookupLabel(string $qualifiedTable, string $idCol, int $idVal, array $labelCandidates): ?string
    {
        [$schema, $table] = self::splitQualified($qualifiedTable);

        $labelCol = self::firstExistingColumn($schema, $table, $labelCandidates);
        if (! $labelCol) return null;

        $val = DB::table($qualifiedTable)->where($idCol, $idVal)->value($labelCol);
        return $val !== null ? (string) $val : null;
    }
}