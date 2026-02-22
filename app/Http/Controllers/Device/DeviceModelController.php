<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use App\Http\Requests\Device\CreateDeviceModelRequest;
use App\Http\Resources\DeviceModelResource;
use App\Models\DeviceModel;
use App\Services\Device\DeviceModelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceModelController extends Controller
{
    public function __construct(private readonly DeviceModelService $service) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeviceModel::class);
        $models = $this->service->list($request->all());
        return $this->collection(DeviceModelResource::collection($models));
    }

    public function show(DeviceModel $deviceModel): JsonResponse
    {
        $this->authorize('view', $deviceModel);
        $deviceModel->load(['sensorSlots.sensorType', 'availableSensorTypes']);
        return $this->success(new DeviceModelResource($deviceModel));
    }

    public function store(CreateDeviceModelRequest $request): JsonResponse
    {
        $this->authorize('create', DeviceModel::class);
        $model = $this->service->create($request->validated());
        return $this->created(new DeviceModelResource($model));
    }

    public function destroy(DeviceModel $deviceModel): JsonResponse
    {
        $this->authorize('delete', $deviceModel);
        $this->service->delete($deviceModel);
        return $this->success(null, 'Device model deleted successfully');
    }
}
