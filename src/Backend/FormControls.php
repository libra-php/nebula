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
        return sprintf('<input type="%s" class="form-control control-%s w-100" id="%s" name="%s" value="%s" placeholder="..." %s>', $type, $type, $name, $name, htmlspecialchars($value ?? ''), $attrs);
    }

    public function textarea(string $name, ?string $value, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        return sprintf('<textarea type="text" class="form-control control-textarea w-100" id="%s" name="%s" placeholder="..." %s>%s</textarea>', $name, $name, $attrs, htmlspecialchars($value ?? ''));
    }

    public function select(string $name, ?string $value, array $options, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        if (isset($options[0]) && is_object($options[0])) {
            // Object (db query: id, name)
            $options = array_map(fn($option) => sprintf('<option value="%s" %s>%s</option>', $option->id, $value == $option->id ? 'selected' : '', htmlspecialchars($option->name)), array_values($options));
        } else {
            // Array (value => label)
            $options = array_map(fn($val, $label) => sprintf('<option value="%s" %s>%s</option>', htmlspecialchars($val), $value == $val ? 'selected' : '', $label), array_keys($options), array_values($options));
        }
        return sprintf('<select name="%s" class="form-select control-select" %s><option disabled>Please select an option</option>%s</select>', $name, $attrs, implode("", $options));
    }

    public function file(string $name, ?string $value, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        $control = sprintf('<input type="file" class="form-control control-file w-100" id="%s" name="%s" %s>', $name, $name, $attrs);
        if ($value && file_exists($value)) {
            $basename = basename($value);
            $control .= "<div class='d-flex'>";
            $control .= sprintf('<div class="d-flex mt-1 me-1"><a class="btn btn-sm btn-secondary" href="%s">View file</a></div>', "/uploads/$basename", $value, $value);
            $control .= sprintf('<div class="d-flex mt-1"><a class="btn btn-sm btn-danger disabled" href="%s">Delete file</a></div>', "/uploads/$basename", $value, $value);
            $control .= "</div>";
        }
        return $control;
    }

    public function checkbox(string $name, ?string $value): string
    {
        return ''; 
    }

    public function switch(string $name, ?string $value): string
    {
        return ''; 
    }

    public function range(string $name, ?string $value): string
    {
        return ''; 
    }
}
