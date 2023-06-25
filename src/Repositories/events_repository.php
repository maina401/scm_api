<?php
namespace Leaf\Repositories;
use Exception;
use Leaf\Helpers\Validator;
use Leaf\Models\User;

class events_repository
{
    public function list_events($data): array
    {
        $events = User::find(session()->get('user_id'))->events()->get();
        return $events->toArray();
    }
    //create_event
    public function create_event($data): array
    {
        Validator::make($data, [
            'title' => 'required|string|unique:events,title',
            'description' => 'required|string',
        ]);
        $event = User::find(session()->get('user_id'))->events()->create((array)$data);
        return $event->toArray();
    }

    //get event
    /**
     * @throws Exception
     */
    public function get_event($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid',
        ]);
        $event = User::find(session()->get('user_id'))->events()->findOrFail($data->id);
        return $event->toArray();
    }

    //update event
    /**
     * @throws Exception
     */
    public function update_event($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid'
        ]);
        $event = User::find(session()->get('user_id'))->events()->findOrFail($data->id);
        $event->update((array)$data);
        return $event->toArray();
    }

    //delete event
    /**
     * @throws Exception
     */
    public function delete_event($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid'
        ]);
        $event = User::find(session()->get('user_id'))->events()->findOrFail($data->id);
        $event->delete();
        return $event->toArray();
    }
}
