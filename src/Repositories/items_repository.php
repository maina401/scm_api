<?php
namespace Leaf\Repositories;
use Exception;
use Leaf\Helpers\ApiException;
use Leaf\Helpers\Validator;
use Leaf\Models\User;

class items_repository
{
    public function list_items($data): array
    {
        $items = User::find(session()->get('user_id'))->items()->get();
        return $items->toArray();
    }
    //create_item
    public function create_item($data): array
    {
        Validator::make($data, [
            'name' => 'required|string|unique:items,name',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'quantity' => 'required|numeric',
        ]);
        $item = User::find(session()->get('user_id'))->items()->create((array)$data);
        return $item->toArray();
    }

    //get item
    /**
     * @throws Exception
     */
    public function get_item($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid',
        ]);
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->id);
        return $item->toArray();
    }

    //update item
    /**
     * @throws Exception
     */
    public function update_item($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid'
        ]);
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->id);
        $item->update((array)$data);
        return $item->toArray();
    }

    //delete item
    /**
     * @throws Exception
     */
    public function delete_item($data): array
    {
        Validator::make($data, [
            'id' => 'required|uuid'
        ]);
        $item = User::find(session()->get('user_id'))->items()->findOrFail($data->id);
        $item->delete();
        return $item->toArray();
    }

}
