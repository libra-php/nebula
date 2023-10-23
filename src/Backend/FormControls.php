<?php

namespace Nebula\Backend;

class FormControls
{
    public function __construct(private ?string $id)
    {}

    public function plain(string $name, ?string $value): string
    {
        return sprintf(
            '<div class="w-100 control-plain" id="%s"><p class="p-1">%s</p></div>',
            $name,
            htmlspecialchars($value ?? "")
        );
    }

    public function input(
        string $name,
        ?string $value,
        string $type = "text",
        string $class = "form-control w-100",
        ...$attrs
    ): string {
        $attrs = implode(" ", $attrs);
        return sprintf(
            '<input type="%s" class="%s control-%s" id="%s" name="%s" value="%s" placeholder="..." %s>',
            $type,
            $class,
            $type,
            $name,
            $name,
            htmlspecialchars($value ?? ""),
            $attrs
        );
    }

    public function textarea(string $name, ?string $value, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        return sprintf(
            '<textarea type="text" class="form-control control-textarea w-100" id="%s" name="%s" placeholder="..." %s>%s</textarea>',
            $name,
            $name,
            $attrs,
            htmlspecialchars($value ?? "")
        );
    }

    public function select(
        string $name,
        ?string $value,
        array $options,
        ...$attrs
    ): string {
        $attrs = implode(" ", $attrs);
        if (isset($options[0]) && is_object($options[0])) {
            // Object (db query: id, name)
            $options = array_map(
                fn($option) => sprintf(
                    '<option value="%s" %s>%s</option>',
                    $option->id,
                    $value == $option->id ? "selected" : "",
                    htmlspecialchars($option->name)
                ),
                array_values($options)
            );
        } else {
            // Array (value => label)
            $options = array_map(
                fn($val, $label) => sprintf(
                    '<option value="%s" %s>%s</option>',
                    htmlspecialchars($val),
                    $value == $val ? "selected" : "",
                    $label
                ),
                array_keys($options),
                array_values($options)
            );
        }
        return sprintf(
            '<select name="%s" class="form-select control-select" %s><option disabled>Please select an option</option>%s</select>',
            $name,
            $attrs,
            implode("", $options)
        );
    }

    public function file(string $name, ?string $value, ...$attrs): string
    {
        $attrs = implode(" ", $attrs);
        $control = sprintf(
            '<input type="file" class="form-control control-file w-100" id="%s" name="%s" %s>',
            $name,
            $name,
            $attrs
        );
        if ($value && file_exists($value)) {
            $basename = basename($value);
            $public_uploads = config("paths.public_uploads");
            $path = sprintf("%s/%s", $public_uploads, $basename);
            $control .= "<div class='d-flex align-items-center control-file p-1'>";
            $control .= sprintf(
                '<label title="%s" class="truncate" width="25" for="%s">%s</label>',
                $basename,
                $name,
                $basename
            );
            $control .= sprintf(
                '<div class="d-flex ms-2"><a title="View" class="btn btn-sm btn-outline-info file-button" href="%s">&#128065;</a></div>',
                $path,
                $value,
                $value
            );
            $control .= sprintf(
                '<div class="d-flex ms-2"><button title="Delete" type="submit" class="btn btn-sm btn-outline-warning file-button" name="delete_file" value="%s" hx-confirm="Are you sure you want to delete this file?" hx-post="%s" hx-target="#module">&#128465;</button></div>',
                $name,
                ""
            );
            $control .= "</div>";
        }
        return $control;
    }

    public function image(string $name, ?string $value, ...$attrs): string
    {
        $control = '';
        if ($value && file_exists($value)) {
            $basename = basename($value);
            $public_uploads = config("paths.public_uploads");
            $path = sprintf("%s/%s", $public_uploads, $basename);
            $control .= sprintf('<img title="%s" src="%s" class="control-image mb-1 rounded-3 border-1 border-secondary" alt="img" />', $basename, $path);
        }
        $control .= $this->file($name, $value, ...$attrs);
        return $control;
    }

    public function checkbox(string $name, ?string $value, ...$attrs): string
    {
        $hidden = $this->input($name, $value, "hidden");
        $checked = intval($value) ? "checked" : '';
        $checkbox = $this->input('', '', "checkbox", "form-check-input", $checked);
        return $hidden.$checkbox;
    }

    public function switch(string $name, ?string $value): string
    {
        $hidden = $this->input($name, $value, "hidden");
        $checked = intval($value) ? "checked" : '';
        $checkbox = $this->input('', '', "checkbox", "form-check-input", $checked);
        return sprintf("<div class='form-check form-switch'>%s</div>", $hidden.$checkbox);
    }

    public function range(string $name, ?string $value): string
    {
        return "";
    }
}
