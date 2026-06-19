<?php
use App\Models\Company;
use App\Support\Theme;

foreach (Company::all() as $c) {
    echo $c->name . ' => ' . ($c->primary_color ?: '(default)') . PHP_EOL;
    echo '  ' . Theme::cssFor($c) . PHP_EOL;
}
