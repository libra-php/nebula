<?php

namespace App\Modules;

use Nebula\Framework\Admin\Module;

class Audit extends Module
{
    public function init(): void
    {
        $this->create = $this->delete = $this->edit = false;
        $this->table_columns = [
            "ID" => "id",
            "User" => "(SELECT name FROM users WHERE id = user_id) as user",
            "Table" => "table_name",
            "Key" => "table_id",
            "Field" => "field",
            "Diff" => "id as diff",
            "Tag" => "tag",
            "Created" => "created_at",
        ];
        $this->table_format = [
            "diff" => function(string $column, string $value) {
                $record = $this->getRecord($value);
                return $this->htmlDiff($record->old_value ?? '', $record->new_value ?? '');
            },
        ];
        $this->filter_links = [
            "All" => "1=1",
            "Register" => "tag = 'REGISTER'",
            "Create" => "tag = 'CREATE'",
            "Update" => "tag = 'UPDATE'",
            "Delete" => "tag = 'DELETE'",
        ];
        $this->search_columns = ["user"];
    }

    function diff($old, $new)
    {
        $matrix = array();
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
                    $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) return array(array('d' => $old, 'i' => $new));
        return array_merge(
            $this->diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
            array_slice($new, $nmax, $maxlen),
            $this->diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen))
        );
    }

    function htmlDiff($old, $new)
    {
        $ret = '<div class="table-diff">';
        $diff = $this->diff(preg_split("/[\s]+/", $old), preg_split("/[\s]+/", $new));
        foreach ($diff as $k) {
            if (is_array($k))
                $ret .= (!empty($k['d']) ? "<span title='Removed' class='diff-remove'>" . implode(' ', $k['d']) . "</span> " : '') .
                    (!empty($k['i']) ? "<span title='Added' class='diff-add'>" . implode(' ', $k['i']) . "</span> " : '');
            else $ret .= $k . ' ';
        }
        $ret .= '</div>';
        return $ret;
    }
}
