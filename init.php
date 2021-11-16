<?php
namespace Skripteria\Snowflake;
use Skripteria\Snowflake\Models\Page;
use Skripteria\Snowflake\Models\Element;
use Db;

function log($message) {
    \Log::info($message);
}

function parse_snowflake($page) {
    $content = $page->markup;
    preg_match_all('|{{(...*)}}|U', $content, $op);
    $tags = [];

    foreach ($op[1] as $key=>$value) {

        $checkval = str_replace(' ','', $value);

        if (strpos($checkval, '|sf(')) {

            $pattern = "|([\w\.\ ]+)|";
            preg_match_all($pattern, $value, $matches);

            $matches = $matches[0];

            foreach ($matches as $mkey=>$match) {
                $matches[$mkey] = $match = trim($match, ' ');
                if (empty($match) || $match == 'sf') unset($matches[$mkey]);
            }

            $i = 0;
            $desc = '';

            foreach($matches as $val) {
                switch ($i) {
                    case 0:
                        $key = explode('.', $val)[0];
                    break;
                    case 1:
                        $type = $val;
                    break;
                    case 2:
                        $desc = $val;
                }
                $i++;
            }

            if (count($matches) > 3 || count($matches) < 2) {
                log("Fehler: Anzahl Matches ungültig!");
            } else {
                $tags[$key]['type'] = $type;
                $tags[$key]['desc'] = $desc;
            }
        }
    }
    sync_db($tags, $page);
}

function sync_db($tags, $page) {

    $filename = $page->getBaseFileName();
    $sfpage = Db::table('skripteria_snowflake_pages')->where('filename', $filename)->first();
    if (! $sfpage) {
        $sfpage = new Page;
        $sfpage->filename = $filename;
        $sfpage->save();
    }
    $elements = Db::table('skripteria_snowflake_elements')->where('page_id', $sfpage->id)->get();
    $types_raw = Db::table('skripteria_snowflake_types')->get();

    $types = [];

    foreach ($types_raw as $type) {
        $types[$type->name] = $type->id;
    }

    $db_array = [];

    foreach($elements as $element){
        $db_array[$element->cms_key]['type_id'] = $element->type_id;
        $db_array[$element->cms_key]['desc'] = $element->desc;
        $db_array[$element->cms_key]['id'] = $element->id;

        // Clean up empty database records
        if (! isset($tags[$element->cms_key])) {
            $el = Element::find($db_array[$element->cms_key]["id"]);
            if (($el->type_id != 3 && empty($el->content)) || ($el->type_id == 3 && empty($el->image->path))) {
                $el->delete();
            } else {
                $el->in_use = 0;
                $el->save();
            }
        }
    }

    foreach ($tags as $cms_key =>$value) {
        if (! isset($types[$value['type']])) {
            log(' Exeption: Type not supported');
            continue;
        }

        if (isset($db_array[$cms_key])) {
            // Update
            $el = Element::find($db_array[$cms_key]["id"]);
            $el->type_id = $types[$value["type"]];
            $el->desc = $value["desc"];
            $el->in_use = 1;
            $el->save();
        } else {
            //Insert
            $el = new Element();
            $el->type = $types[$value["type"]];
            $el->desc = $value["desc"];
            $el->page_id = $sfpage->id;
            $el->cms_key = $cms_key;
            $el->save();
        }

    }
}
