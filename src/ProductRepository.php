<?php
declare(strict_types=1);

final class ProductRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Lista los productos activos del catálogo con su categoría y productor.
     *
     * @return list<array<string, mixed>>
     */
    public function listarCatalogo(): array
    {
        $sql = <<<SQL
            SELECT p.id,
                   p.nombre,
                   p.imagen,
                   p.precio_referencia,
                   p.unidad,
                   p.disponibilidad,
                   c.slug   AS categoria_slug,
                   c.nombre AS categoria,
                   pr.nombre    AS productor,
                   pr.municipio AS municipio
              FROM productos p
              JOIN categorias  c  ON c.id  = p.categoria_id
              JOIN productores pr ON pr.id = p.productor_id
             WHERE p.activo = TRUE
             ORDER BY p.nombre
            SQL;

        $rows = $this->db->query($sql)->fetchAll();

        return array_map(static function (array $r): array {
            $r['id']                = (int) $r['id'];
            $r['precio_referencia'] = (float) $r['precio_referencia'];
            $r['disponibilidad']    = (int) $r['disponibilidad'];
            return $r;
        }, $rows);
    }
}
