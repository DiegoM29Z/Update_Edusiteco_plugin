# Guía Rápida: Integrar "Convocatorias" en el Menú Superior de `edusiteco-theme`

Esta guía explica los **2 ajustes** rápidos en el tema **`edusiteco-theme`** para que el botón **Convocatorias** aparezca en la barra superior conectado al plugin **SIGEC-WP** y sin amontonarse con la barra de búsqueda.

> **Nota:** Al activar el plugin **SIGEC-WP**, WordPress crea automáticamente las 4 páginas necesarias (`portal-de-contratacion`, `acceso-proveedores`, `panel-de-proveedores` y `postulacion`) con sus shortcodes. No es necesario crearlas manualmente.

---

## Parte 1: Agregar "Convocatorias" al Menú Superior del Tema

**Archivo a editar:**
`wp-content/themes/edusiteco-theme/inc/nav-walkers.php`

### Paso 1.1 — Obtener la URL de la página de contratación (Escritorio)
Busca la función `edusiteco_default_menu()` (alrededor de la **línea 270**), justo debajo de donde se define `$psico_url`, y agrega las 2 líneas de `$contratacion_page`:

```php
            $psico_page = get_page_by_path('psicoorientacion-escolar');
            $psico_url = $psico_page ? get_permalink($psico_page->ID) : '#';

            $contratacion_page = get_page_by_path('portal-de-contratacion');
            $contratacion_url = $contratacion_page ? get_permalink($contratacion_page->ID) : home_url('/portal-de-contratacion/');
            ?>
```

### Paso 1.2 — Conectar también el submenú "Noticias → Convocatorias"
Unas líneas más abajo (alrededor de la **línea 295**), reemplaza estas dos líneas:

```php
            // ANTES:
            $convocatorias_category = get_category_by_slug('convocatorias');
            $convocatorias_url = $convocatorias_category ? get_category_link($convocatorias_category->term_id) : '#';
```

Por esta sola línea:

```php
            // DESPUÉS:
            $convocatorias_url = $contratacion_url;
```

### Paso 1.3 — Agregar el botón "Convocatorias" en la barra principal (Escritorio)
Al final de la función `edusiteco_default_menu()` (alrededor de la **línea 350**), justo debajo del `<li>` de **Psicoorientación** y antes de cerrar `</ul>`, pega este bloque:

```php
        <li class="header-group">
            <a class="<?= $link_class ?> text-sm font-semibold underline-link transition-all px-3 py-2 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                href="<?= esc_url($contratacion_url) ?>">
                <span>Convocatorias</span>
            </a>
        </li>
    </ul>
```

### Paso 1.4 — Agregar el enlace en el Menú Móvil (Celular)
Más abajo en el mismo archivo, dentro de la función `edusiteco_default_mobile_menu()`:

1. Debajo de `$psico_url` (alrededor de la **línea 385**), agrega:
```php
    $contratacion_page = get_page_by_path('portal-de-contratacion');
    $contratacion_url = $contratacion_page ? get_permalink($contratacion_page->ID) : home_url('/portal-de-contratacion/');
```

2. Al final de la función (alrededor de la **línea 448**), justo debajo del enlace de **Psicoorientación**, agrega:
```php
    <a class="block pl-4 pr-4 py-3.5 text-base font-medium <?= $link_classes ?> border-b border-border-light dark:border-border-dark last:border-b-0 transition-all"
        href="<?= esc_url($contratacion_url) ?>">
        Convocatorias
    </a>
```

---

## Parte 2: Ajustar el CSS para que NO se amontone el menú con el buscador

### Paso 2.1 — Ajustar espaciado y evitar salto de línea en `nav-menu.css`
**Archivo a editar:**
`wp-content/themes/edusiteco-theme/src/css/nav-menu.css`

Busca el bloque `DESKTOP MENU` (alrededor de la **línea 40**):

```css
/* ANTES: */
/* Sin imagen - modo claro */
.no-header-image .header-group a {
    position: relative;
    padding: 0.5rem 0.75rem;
}
```

Y reemplázalo por este bloque completo:

```css
/* DESPUÉS: */
/* Evitar que elementos de 2 palabras ("La Institución") se partan en dos líneas */
.header-group > a {
    white-space: nowrap;
}

/* Separación limpia entre navegación e input de búsqueda */
header .max-w-7xl > .flex {
    gap: 1.25rem;
}

header .search-form .search-field {
    max-width: 175px;
}

/* Sin imagen - modo claro */
.no-header-image .header-group a {
    position: relative;
    padding: 0.5rem 0.55rem;
}
```

### Paso 2.2 — Evitar caché viejo del navegador en `functions.php`
**Archivo a editar:**
`wp-content/themes/edusiteco-theme/functions.php`

En la **línea 151**, cambia `_S_VERSION` por `filemtime(...)` para que el navegador recargue los estilos inmediatamente:

```php
// ANTES:
wp_enqueue_style( 'edusiteco-nav-menu', get_template_directory_uri() . '/src/css/nav-menu.css', array( 'edusiteco-base' ), _S_VERSION );

// DESPUÉS:
wp_enqueue_style( 'edusiteco-nav-menu', get_template_directory_uri() . '/src/css/nav-menu.css', array( 'edusiteco-base' ), filemtime( get_template_directory() . '/src/css/nav-menu.css' ) );
```

