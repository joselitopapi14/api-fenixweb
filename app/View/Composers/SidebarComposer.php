<?php

namespace App\View\Composers;

use Illuminate\View\View;

class SidebarComposer
{
    public function compose(View $view): void
    {
        // Obtener ciudadanos con cumpleaños hoy y mañana que no han sido felicitados
        $hoy = now();
        $manana = $hoy->copy()->addDay();

    }
}
