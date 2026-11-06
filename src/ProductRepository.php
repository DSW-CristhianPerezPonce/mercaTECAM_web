<?php
declare(strict_types=1);

final class ProductRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Lista los productos activos del catálogo con su categoría y productor.
     * Filtros opcionales (HU-02), combinables:
     *  - $categoria: slug de la categoría (p. ej. "frutas")
     *  - $busqueda:  texto contenido en el nombre, sin distinguir mayúsculas ni tildes
     *
     * @return list<array<string, mixed>>
     */
    public function listarCatalogo(?string $categoria = null, ?string $busqueda = null): array
    {
        $where  = ['p.activo = TRUE'];
        $params = [];

        if ($categoria !== null && $categoria !== '') {
            $where[] = 'c.slug = :categoria';
            $params['categoria'] = $categoria;
        }

        if ($busqueda !== null && $busqueda !== '') {
            // Se escapan los comodines de LIKE para que "%" o "_" se busquen literalmente.
            $where[] = "unaccent(p.nombre) ILIKE '%' || unaccent(:busqueda) || '%' ESCAPE '\\'";
            $params['busqueda'] = addcslashes($busqueda, '\\%_');
        }

        $sql = 'SELECT p.id,
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
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY p.nombre';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(static function (array $r): array {
            $r['id']                = (int) $r['id'];
            $r['precio_referencia'] = (float) $r['precio_referencia'];
            $r['disponibilidad']    = (int) $r['disponibilidad'];
            return $r;
        }, $stmt->fetchAll());
    }

    /**
     * Todas las categorías con la cantidad de productos activos de cada una
     * (se listan también las que aún no tienen productos).
     *
     * @return list<array{slug: string, nombre: string, total: int}>
     */
    public function categoriasConConteo(): array
    {
        $sql = 'SELECT c.slug, c.nombre, COUNT(p.id) AS total
                  FROM categorias c
                  LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo = TRUE
                 GROUP BY c.id, c.slug, c.nombre
                 ORDER BY c.id';

        return array_map(static function (array $r): array {
            $r['total'] = (int) $r['total'];
            return $r;
        }, $this->db->query($sql)->fetchAll());
    }
}
