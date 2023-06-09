<?php

namespace Actions;

use PHPUnit\Framework\TestCase;
use Grimarina\CityBike\Repositories\StationsRepository;
use Grimarina\CityBike\http\{Request, SuccessfulResponse, ErrorResponse};
use Grimarina\CityBike\Actions\Stations\FindAllStations;
use Grimarina\CityBike\Exceptions\StationNotFoundException;

class FindAllStationsTest extends TestCase
{
    private $stationsRepository;
    private $mockRequest;

    protected function setUp(): void
    {
        // Create a mock instance of StationsRepository and Request for testing
        $this->stationsRepository = $this->createMock(StationsRepository::class);
        $this->mockRequest = $this->createMock(Request::class);
    }

    public function testItReturnsSuccessfulResponseWithStatus200(): void
    {
        // Define the expected stations data
        $stations = [
            ['id' => 1, 'name_fi' => 'Test Station 1', 'address_fi' => 'Test Address 1', 'capacity' => 10, 'coordinate_x' => 60.123, 'coordinate_y' => 24.456],
            ['id' => 2, 'name_fi' => 'Test Station 2', 'address_fi' => 'Test Address 2', 'capacity' => 20, 'coordinate_x' => 60.456, 'coordinate_y' => 24.789]
        ];

        // Set up the expectations for the mock methods
        $this->stationsRepository
            ->expects($this->once())
            ->method('getEntries')
            ->willReturn(2);

        $this->stationsRepository
            ->expects($this->once())
            ->method('getAll')
            ->with(1, 10)
            ->willReturn($stations);

        $this->mockRequest
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnMap([
                ['page', '1'],
                ['limit', '10'],
            ]);

        $action = new FindAllStations($this->stationsRepository);

        // Execute the action and get the response
        $response = $action->handle($this->mockRequest);

        // Assert the response type, status, and payload values
        $this->assertInstanceOf(SuccessfulResponse::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertEquals($stations, $response->payload()['data']['stations']);
        $this->assertEquals(2, $response->payload()['data']['entries']);
        $this->assertIsFloat($response->payload()['data']['stations'][0]['coordinate_x']);
        $this->assertIsFloat($response->payload()['data']['stations'][0]['coordinate_y']);
    }

    public function testItReturnsErrorResponseWithStatus404IfStationsNotFound(): void
    {
        // Set up the expectations for the mock methods
        $this->stationsRepository
            ->expects($this->once())
            ->method('getEntries')
            ->willReturn(2);

        $this->stationsRepository
            ->expects($this->once())
            ->method('getAll')
            ->with(1)
            ->willThrowException(new StationNotFoundException('Stations not found.'));

        $this->mockRequest
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnMap([
                ['page', '1'],
                ['limit', '10'],
            ]);

        $action = new FindAllStations($this->stationsRepository);
        // Execute the action and get the response
        $response = $action->handle($this->mockRequest);

        // Assert the response type, status, and payload values
        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(404, $response->status());
        $this->assertEquals('Stations not found.', $response->payload()['reason']);
    }

    public function testItReturnsErrorResponseWithStatus400IfParamsInvalid(): void
    {
        // Set up the expectations for the mock methods
        $this->mockRequest
            ->expects($this->exactly(2))
            ->method('query')
            ->willReturnMap([
                ['page', 'invalid_page'],
                ['limit', 'invalid_limit'],
            ]);

        $action = new FindAllStations($this->stationsRepository);
        // Execute the action and get the response
        $response = $action->handle($this->mockRequest);

        // Assert the response type, status, and payload values
        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(400, $response->status());
        $this->assertEquals('Invalid parameters.', $response->payload()['reason']);
    }
}
