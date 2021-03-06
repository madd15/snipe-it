<?php
namespace App\Http\Transformers;

use App\Models\Statuslabel;
use Illuminate\Database\Eloquent\Collection;
use Gate;
use App\Helpers\Helper;

class StatuslabelsTransformer
{

    public function transformStatuslabels (Collection $statuslabels)
    {
        $array = array();
        foreach ($statuslabels as $statuslabel) {
            $array[] = self::transformStatuslabel($statuslabel);
        }
        return (new DatatablesTransformer)->transformDatatables($array);
    }

    public function transformStatuslabel (Statuslabel $statuslabel)
    {
        $array = [
            'id' => (int) $statuslabel->id,
            'name' => e($statuslabel->name),
            'type' => $statuslabel->getStatuslabelType(),
            'color' => ($statuslabel->color) ? e($statuslabel->color) : null,
            'show_in_nav' => ($statuslabel->show_in_nav=='1') ? true : false,
            'notes' => e($statuslabel->notes),
            'created_at' => Helper::getFormattedDateObject($statuslabel->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($statuslabel->updated_at, 'datetime'),
        ];

        $permissions_array['available_actions'] = [
            'update' => Gate::allows('update', Statuslabel::class) ? true : false,
            'delete' => Gate::allows('delete', Statuslabel::class) ? true : false,
        ];
        $array += $permissions_array;

        return $array;
    }



}
