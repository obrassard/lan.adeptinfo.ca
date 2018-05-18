<?php


namespace App\Services\Implementation;


use App\Model\Lan;
use App\Repositories\Implementation\LanRepositoryImpl;
use App\Services\LanService;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Seatsio\SeatsioClient;
use Seatsio\SeatsioException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class LanServiceImpl implements LanService
{
    protected $lanRepository;

    /**
     * LanServiceImpl constructor.
     * @param LanRepositoryImpl $lanRepositoryImpl
     */
    public function __construct(LanRepositoryImpl $lanRepositoryImpl)
    {
        $this->lanRepository = $lanRepositoryImpl;
    }

    public function createLan(Request $input): Lan
    {

        // Internal validation

        $lanValidator = Validator::make($input->all(), [
            'lan_start' => 'required|after:seat_reservation_start|after:tournament_reservation_start',
            'lan_end' => 'required|after:lan_start',
            'seat_reservation_start' => 'required|after_or_equal:now',
            'tournament_reservation_start' => 'required|after_or_equal:now',
            'event_key_id' => 'required|string|max:255',
            'public_key_id' => 'required|string|max:255',
            'secret_key_id' => 'required|string|max:255',
            'price' => 'integer|min:0',
            'rules' => 'string'
        ]);

        if ($lanValidator->fails()) {
            throw new BadRequestHttpException($lanValidator->errors());
        }

        // Seats.io validation

        $seatsClient = new SeatsioClient($input['secret_key_id']);
        // Test if secret key is id valid
        try {
            $seatsClient->charts()->listAllTags();
        } catch (SeatsioException $exception) {
            throw new BadRequestHttpException(json_encode([
                "secret_key_id" => [
                    'Secret key id: ' . $input['secret_key_id'] . ' is not valid.'
                ]
            ]));
        }

        // Test if event key id is valid
        try {
            $seatsClient->events()->retrieve($input['event_key_id']);
        } catch (SeatsioException $exception) {
            throw new BadRequestHttpException(json_encode([
                "event_key_id" => [
                    'Event key id: ' . $input['event_key_id'] . ' is not valid.'
                ]
            ]));
        }

        return $this->lanRepository->createLan
        (
            new DateTime($input->input('lan_start')),
            new DateTime($input->input('lan_end')),
            new DateTime($input->input('seat_reservation_start')),
            new DateTime($input->input('tournament_reservation_start')),
            $input->input('event_key_id'),
            $input->input('public_key_id'),
            $input->input('secret_key_id'),
            intval($input->input('price')),
            $input->input('rules')
        );
    }

    public function updateRules(Request $input, string $lanId): array
    {
        $rulesValidator = Validator::make([
            'lan_id' => $lanId,
            'text' => $input->input('text')
        ], [
            'lan_id' => 'required|integer',
            'text' => 'required|string',
        ]);

        if ($rulesValidator->fails()) {
            throw new BadRequestHttpException($rulesValidator->errors());
        }

        $lan = $this->lanRepository->findLanById($lanId);

        $this->lanExists($lanId, $lan);

        $this->lanRepository->updateLanRules($lan, $input['text']);

        return ["text" => $input['text']];
    }

    public function getRules(string $lanId): array
    {
        $rulesValidator = Validator::make([
            'lan_id' => $lanId,
        ], [
            'lan_id' => 'required|integer'
        ]);

        if ($rulesValidator->fails()) {
            throw new BadRequestHttpException($rulesValidator->errors());
        }

        $lan = $this->lanRepository->findLanById($lanId);

        $this->lanExists($lanId, $lan);

        return ["text" => $lan->rules];
    }

    private function lanExists(int $lanId, ?Lan $lan)
    {
        if ($lan == null) {
            throw new BadRequestHttpException(json_encode([
                "lan_id" => [
                    'Lan with id ' . $lanId . ' doesn\'t exist'
                ]
            ]));
        }
    }
}