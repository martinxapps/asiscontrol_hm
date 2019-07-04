<?php

/*
 * This file is part of the Ocrend Framewok 3 package.
 *
 * (c) Ocrend Software <info@ocrend.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ocrend\Kernel\Helpers;

/**
 * Helper con funciones útiles para trabajar con evío de correos mediante mailer.
 *
 * @author Brayan Narváez <prinick@ocrend.com>
 */

class Sections
{

    /**
     * Ruta en la qeu están guardados los templates.
     *
     * @var string
     */
    const TEMPLATES_ROUTE = ___ROOT___ . 'assets/sections/';

    /**
     * Carga una plantilla y sistituye su contenido.
     *
     * @param array $content: Contenido de cada elemento
     * @param int $template: Plantilla seleccionada
     *
     * @return string plantilla llena
     */
    public static function loadTemplate(array $content, string $template): string
    {

        # Cargar contenido
        $tpl = Files::read_file(self::TEMPLATES_ROUTE . $template);

        # Reempalzar contenido
        foreach ($content as $index => $html) {
            $tpl = str_replace($index, $html, $tpl);
        }

        return $tpl;
    }

}
