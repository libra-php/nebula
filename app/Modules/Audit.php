<?php

namespace App\Modules;

use App\Models\Audit as AuditModel;
use Nebula\Alerts\Flash;
use Nebula\Backend\Module;

class Audit extends Module
{
    public function __construct()
    {
        $this->table_create = $this->table_edit = $this->table_destroy = false;
        $this->name_col = "id";
        $this->table_columns = [
            "audit.id" => "ID",
            "users.name" => "User",
            "audit.table_name" => "Table",
            "audit.table_id" => "ID",
            "audit.field" => "Field",
            "audit.id as diff" => "Diff",
            "audit.message" => "Message",
            "audit.created_at" => "Created At",
            "audit.old_value" => null,
            "audit.new_value" => null,
        ];
        $this->where = [["old_value IS NOT NULL"], ["new_value IS NOT NULL"]];
        $this->table_format = [
            "diff" => fn($row, $column) => $this->formatDiff($row),
        ];
        $this->joins = ["INNER JOIN users ON audit.user_id = users.id"];
        $this->search = ["table_name", "table_id", "field", "name"];
        $this->filter_datetime = "created_at";
        $this->filter_links = [
            "Me" => "user_id = " . user()->id,
            "Others" => "user_id != " . user()->id,
            "All" => "1=1",
        ];
        $this->filter_select = [
            "users.name" => "User",
            "table_name" => "Table",
        ];
        $this->select_options = [
            "users.name" => db()->selectAll(
                "SELECT name as id, concat(name, ' (', email, ')') as name FROM users ORDER BY name"
            ),
            "table_name" => db()->selectAll(
                "SELECT distinct table_name as id, table_name as name FROM audit ORDER BY table_name"
            ),
        ];
        $this->addRowAction(
            "undo_change",
            "Undo",
            "Are you sure you want to restore this value?"
        );
        parent::__construct("audit");
    }

    public function formatDiff($audit): string
    {
        return $this->htmlDiff(
            $audit->old_value ?? "NULL",
            $audit->new_value ?? "NULL"
        );
    }

    private function diff(mixed $old, mixed $new)
    {
        $matrix = [];
        $maxlen = 0;
        foreach ($old as $oindex => $ovalue) {
            $nkeys = array_keys($new, $ovalue);
            foreach ($nkeys as $nindex) {
                $matrix[$oindex][$nindex] = isset(
                    $matrix[$oindex - 1][$nindex - 1]
                )
                    ? $matrix[$oindex - 1][$nindex - 1] + 1
                    : 1;
                if ($matrix[$oindex][$nindex] > $maxlen) {
                    $maxlen = $matrix[$oindex][$nindex];
                    $omax = $oindex + 1 - $maxlen;
                    $nmax = $nindex + 1 - $maxlen;
                }
            }
        }
        if ($maxlen == 0) {
            return [["d" => $old, "i" => $new]];
        }
        return array_merge(
            $this->diff(
                array_slice($old, 0, $omax),
                array_slice($new, 0, $nmax)
            ),
            array_slice($new, $nmax, $maxlen),
            $this->diff(
                array_slice($old, $omax + $maxlen),
                array_slice($new, $nmax + $maxlen)
            )
        );
    }

    private function htmlDiff(string $old, string $new): string
    {
        if (trim($old) === "") {
            $old = "(empty string)";
        }
        if (trim($new) === "") {
            $new = "(empty string)";
        }
        $ret = '<div class="d-flex">';
        $diff = $this->diff(
            preg_split("/[\s]+/", $old),
            preg_split("/[\s]+/", $new)
        );
        foreach ($diff as $k) {
            if (is_array($k)) {
                $ret .=
                    (!empty($k["d"])
                        ? "<div title='Removed' class='audit-old truncate'>" .
                            implode(" ", $k["d"]) .
                            "</div> "
                        : "") .
                    (!empty($k["i"])
                        ? "<div title='Added' class='audit-new truncate'>" .
                            implode(" ", $k["i"]) .
                            "</div> "
                        : "");
            } else {
                $ret .= $k . " ";
            }
        }
        $ret .= "</div>";
        return $ret;
    }

    protected function hasRowActionPermission(string $name, string $id): bool
    {
        $audit = AuditModel::find($id);
        if (in_array($audit->message, ["UPDATE", "UNDO"])) {
            return true;
        }
        return false;
    }

    protected function processTableRequest(): void
    {
        parent::processTableRequest();
        if (request()->has("undo_change")) {
            $audit = AuditModel::find(request()->id);
            $result = db()->query(
                "UPDATE $audit->table_name SET $audit->field = ? WHERE id = ?",
                $audit->old_value,
                $audit->table_id
            );
            if ($result) {
                Flash::addFlash("success", "Old value restored successfully");
                $this->audit(
                    user()->id,
                    $audit->table_name,
                    $audit->table_id,
                    $audit->field,
                    $audit->old_value,
                    "UNDO"
                );
            } else {
                Flash::addFlash(
                    "warning",
                    "Oops! Couldn't undo change for this record"
                );
            }
            request()->remove("undo_change");
            echo $this->indexPartial();
            die();
            exit();
        }
    }
}
