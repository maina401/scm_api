<?php

namespace Leaf\Repositories;

use Exception;
use Leaf\Helpers\Validator;
use Leaf\Models\Event;
use Leaf\Models\ItemEvent;
use Leaf\Models\User;

class item_events_repository
{
    //list_item_events
    /**
     * @throws Exception
     */
    public function list_item_events($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
        ]);
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
        $item_events = ItemEvent::where("item_id", $item->id)
            ->get([
                "id",
                "item_id",
                "event_id",
                "value",
            ]);
        $events = Event::whereIn("id", $item_events->pluck("event_id")->toArray())->get();


        //merge item_events with its value from
        foreach ($events as $event) {
            $event->value = $item_events->where("event_id", $event->id)->first()->value;
        }
        return [
            "item" => $item->toArray(),
            "events" => $events->toArray()
        ];
    }

    //create_item_event
    public function create_item_event($data): array
    {
        Validator::make($data, [
            'item_id' => 'required|uuid',
            'event_id' => 'required|uuid',
            "value" => "required",
        ]);
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);

        $item=ItemEvent::create([
            "item_id" => $item->id,
            "event_id" => $data->event_id,
            "value" => $data->value,
            "meta" => $data->meta ?? null
        ]);
        //return the item and its events
        return $item->toArray();
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
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
        $item->events()->updateExistingPivot($data->event_id, ["value" => $data->value, "meta" => $data->meta ?? null]);
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
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->item_id);
        $item->events()->detach($data->event_id);
        //return the item and its events
        $item_events = $item->events()->get();
        return $item_events->toArray();
    }
}
