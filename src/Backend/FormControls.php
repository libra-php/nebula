<?php

namespace Nebula\Backend; 

class FormControls
{
    public function plain(string $name, ?string $value): string
    {
        return sprintf('<div class="w-100 control-plain" id="%s"><p class="p-1">%s</p></div>', $name, htmlspecialchars($value ?? ''));
    }

    public function input(string $name, ?string $value, $type = 'text', ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        return sprintf('<input type="%s" class="form-control control-input w-100" id="%s" name="%s" value="%s" placeholder="..." %s>', $type, $name, $name, htmlspecialchars($value ?? ''), $attrs);
    }

    public function textarea(string $name, ?string $value, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        return sprintf('<textarea type="text" class="form-control control-textarea w-100" id="%s" name="%s" placeholder="..." %s>%s</textarea>', $name, $name, $attrs, htmlspecialchars($value ?? ''));
    }

    public function select(string $name, ?string $value, array $options, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        $options = array_map(fn($key, $opt) => sprintf('<option value="%s" %s>%s</option>', $opt->id, $value == $opt->id ? 'selected' : '', htmlspecialchars($opt->name)), array_keys($options), array_values($options));
        return sprintf('<select name="%s" class="form-select control-select" %s><option disabled>Please select an option</option>%s</select>', $name, $attrs, implode("", $options));
    }

    public function checkbox(): string
    {
        return ''; 
    }

    public function toggle(): string
    {
        return ''; 
    }
}
