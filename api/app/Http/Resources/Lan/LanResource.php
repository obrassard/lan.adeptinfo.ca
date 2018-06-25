<?php

namespace App\Http\Resources\Lan;

use App\Model\Lan;
use Illuminate\Http\Resources\Json\Resource;

class LanResource extends Resource
{

    protected $reservedPlaces;

    public function __construct(Lan $resource, int $reservedPlaces)
    {
        $this->reservedPlaces = $reservedPlaces;
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $fields = explode(',', $request->input('fields'));
        if (substr_count($request->input('fields'), ',') == 0) {
            return [
                'id' => $this->id,
                'name' => $this->name,
                'lan_start' => $this->lan_start,
                'lan_end' => $this->lan_end,
                'seat_reservation_start' => $this->seat_reservation_start,
                'tournament_reservation_start' => $this->tournament_reservation_start,
                'longitude' => number_format($this->longitude, 7),
                'latitude' => number_format($this->latitude, 7),
                'places' => [
                    'reserved' => $this->reservedPlaces,
                    'total' => $this->places
                ],
                'price' => $this->price,
                'rules' => $this->rules,
                'description' => $this->description,
            ];
        } else {
            return [
                'id' => $this->id,
                'name' => $this->when(in_array("name", $fields), $this->name),
                'lan_start' => $this->when(in_array("lan_start", $fields), $this->lan_start),
                'lan_end' => $this->when(in_array("lan_end", $fields), $this->lan_end),
                'seat_reservation_start' => $this->when(in_array("seat_reservation_start", $fields), $this->seat_reservation_start),
                'tournament_reservation_start' => $this->when(in_array("tournament_reservation_start", $fields), $this->tournament_reservation_start),
                'longitude' => $this->when(in_array("longitude", $fields), number_format($this->longitude, 7)),
                'latitude' => $this->when(in_array("latitude", $fields), number_format($this->latitude, 7)),
                "places" => $this->when(in_array("places", $fields), [
                    "reserved" => $this->reservedPlaces,
                    "total" => $this->places,
                ]),
                'price' => $this->when(in_array("price", $fields), $this->price),
                'rules' => $this->when(in_array("rules", $fields), $this->rules),
                'description' => $this->when(in_array("description", $fields), $this->description),
            ];
        }
    }
}
