<?php

namespace Nebula\Backend; 

class FormControls
{
    public function plain($name, $value)
    {
        return sprintf('<div class="w-100" id="%s"><p class="p-1">%s</p></div>', $name, htmlspecialchars($value ?? ''));
    }

    public function input($name, $value, $type = 'text', ...$attrs)
    {
        $attrs = implode(" ", $attrs);
        return sprintf('<input type="%s" class="form-control w-100" id="%s" name="%s" value="%s" placeholder="..." %s>', $type, $name, $name, htmlspecialchars($value ?? ''), $attrs);
    }

    public function textarea($name, $value, ...$attrs)
    {
        $attrs = implode(" ", $attrs);
        return sprintf('<textarea type="text" class="form-control w-100" id="%s" name="%s" placeholder="..." %s>%s</textarea>', $name, $name, $attrs, htmlspecialchars($value ?? ''));
    }
}
