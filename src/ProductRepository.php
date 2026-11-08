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
     * Ficha completa de un producto activo (HU-03). Devuelve null si no existe o está inactivo.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        // productos.id es SERIAL (INTEGER de PostgreSQL); fuera de rango no puede existir.
        if ($id < 1 || $id > 2147483647) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT p.id,
                    p.nombre,
                    p.descripcion,
                    p.imagen,
                    p.precio_referencia,
                    p.unidad,
                    p.disponibilidad,
                    p.especificaciones,
                    p.practicas_agroecologicas,
                    p.valor_agregado,
                    c.slug   AS categoria_slug,
                    c.nombre AS categoria,
                    pr.nombre     AS productor,
                    pr.municipio  AS municipio,
                    pr.asociacion AS asociacion
               FROM productos p
               JOIN categorias  c  ON c.id  = p.categoria_id
               JOIN productores pr ON pr.id = p.productor_id
              WHERE p.id = :id AND p.activo = TRUE'
        );
        $stmt->execute(['id' => $id]);
        $r = $stmt->fetch();
        if ($r === false) {
            return null;
        }

        $r['id']                = (int) $r['id'];
        $r['precio_referencia'] = (float) $r['precio_referencia'];
        $r['disponibilidad']    = (int) $r['disponibilidad'];
        return $r;
    }

    /**
     * Datos vigentes de varios productos activos para el carrito (HU-04).
     * Ids inválidos o inexistentes se ignoran.
     *
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    public function obtenerParaCarrito(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn ($id) => $id >= 1 && $id <= 2147483647)));
        if ($ids === []) {
            return [];
        }

        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT p.id, p.nombre, p.imagen, p.precio_referencia, p.unidad, p.disponibilidad,
                    pr.nombre AS productor
               FROM productos p
               JOIN productores pr ON pr.id = p.productor_id
              WHERE p.activo = TRUE AND p.id IN ($marcas)
              ORDER BY p.nombre"
        );
        $stmt->execute($ids);

        return array_map(static function (array $r): array {
            $r['id']                = (int) $r['id'];
            $r['precio_referencia'] = (float) $r['precio_referencia'];
            $r['disponibilidad']    = (int) $r['disponibilidad'];
            $r['imagen']            = imagen_producto($r['imagen']);
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
