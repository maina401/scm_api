<?php
namespace Leaf\Repositories;
use Exception;
use Leaf\Helpers\Validator;
use Leaf\Models\User;

class item_events_repository
{
   //list_item_events
    public function list_item_events($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
        ]);
        $item=User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
         $item_events = $item->events()
             ->get([
                 "events.id",
                "events.title",
                "events.description",
                "item_events.value"
         ]);

         //remove pivot
            foreach ($item_events as $item_event){
                unset($item_event->pivot);
            }

         return [
                "item"=>$item->toArray(),
                "events"=>$item_events->toArray()
            ]
         ;
    }

    //create_item_event
    public function create_item_event($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
            'event_id' => 'required|uuid',
            "value" => "required",
        ]);
        $item=User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);

        $item->events()->attach($data->event_id,["value"=>$data->value]);
        //return the item and its events
        $item_events = $item->events()->get();
        return $item_events->toArray();
    }

    //update_item_event
    /**
     * @throws Exception
     */
    public function update_item_event($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
            'event_id' => 'required|uuid',
            "value" => "required",
        ]);
        $item=User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
        $item->events()->updateExistingPivot($data->event_id,["value"=>$data->value,"meta"=>$data->meta??null]);
        //return the item and its events
        $item_events = $item->events()->get();
        return $item_events->toArray();
    }

    //delete_item_event
    /**
     * @throws Exception
     */
    public function delete_item_event($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
            'event_id' => 'required|uuid',
        ]);
        $item=User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
        $item->events()->detach($data->event_id);
        //return the item and its events
        $item_events = $item->events()->get();
        return $item_events->toArray();
    }
}
