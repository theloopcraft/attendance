<?php

namespace App\Actions\Custom;

use App\Models\FeederLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Lorisleiva\Actions\Concerns\AsAction;

class GetAttendanceLogsFromCustomFeeder
{
    use AsAction;

    public function handle(string $startDate, string $endDate): void
    {
//        $results = $this->getResults($startDate, $endDate);
        $results = $this->formatResults(json_decode(file_get_contents(public_path('custom_feed.json'))));

        $results->each(function ($result) {
            FeederLog::updateOrCreate($result);
        });

    }


    public function getResults(string $startDate, string $endDate)
    {
        $serverName = ".\SQLEXPRESS";
        $connectionInfo = ['Database' => 'NEWCOSEC', 'UID' => 'super', 'PWD' => '1234'];
        $conn = sqlsrv_connect($serverName, $connectionInfo);

        if (!$conn) {
            echo 'Connection could not be established.<br />';
            exit(print_r(sqlsrv_errors(), true));
        }

//        $startDate = '2024-11-01';
//        $endDate = '2024-11-02';

        $sql = $this->sqlQuery();

        $params = [$startDate, $endDate];
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            exit(print_r(sqlsrv_errors(), true));
        }

        $results = [];

        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = new Fluent($row);
        }

        return $this->formatResults($results);
    }

    public function sqlQuery(): string
    {
        return "SELECT
        IndexNo AS RecId,
        UserID AS EmployeeNo,
        UserName,
        FORMAT(EventDateTime_D, 'yyyy-MM-dd HH:mm:ss') AS InOutDateTime,
        EntryExitType AS EntryExitTypeCode,
        CASE EntryExitType
            WHEN 0 THEN 'IN'
            WHEN 1 THEN 'OUT'
            ELSE 'Unknown'
        END AS EntryExitType,
        MasterControllerID AS ReaderId,
        CASE
            WHEN (MasterControllerID = 0 AND Panel_Door_Type = 0 AND EvtCred = 0) THEN 9
            ELSE Panel_Door_Type
        END AS ReaderTypeCode,
        CASE
            WHEN Panel_Door_Type = 6 THEN 'Path Reader'
            WHEN Panel_Door_Type = 9 THEN 'Vega Reader'
            WHEN Panel_Door_Type = 21 THEN 'Face Reader'
            WHEN (MasterControllerID = 0 AND Panel_Door_Type = 0 AND EvtCred = 0) THEN 'Mobile'
            ELSE 'Unknown'
        END AS ReaderType,
        CASE
            WHEN (MasterControllerID = 0 AND Panel_Door_Type = 0 AND EvtCred = 0) THEN 'Mobile'
            ELSE device_name
        END AS ReaderName,
        EvtCred AS EvtCredCode,
        CASE
            WHEN EvtCred = 2 THEN 'Card'
            WHEN EvtCred = 4 THEN 'Finger'
            WHEN EvtCred = 128 THEN 'Mobile'
            WHEN EvtCred = 64 THEN 'Face'
            WHEN (MasterControllerID = 0 AND Panel_Door_Type = 0 AND EvtCred = 0) THEN 'Mobile'
            ELSE 'Unknown'
        END AS EvtCred
        FROM Mx_VEW_APIUserAttendanceEvents
        WHERE Panel_Door_Type IN (9, 21)
        AND EventDateTime_D BETWEEN ? AND ?
        ";
    }

    protected function formatResults(array $results): Collection
    {
        return collect($results)->map(function ($result) {
            return [
                'unique_id' => $result->RecId,
                'staff_no' => $result->EmployeeNo,
                'action_at' => $result->InOutDateTime,
                'name' => $result->UserName,
                'action' => $result->EntryExitType,
                'action_code' => $result->EntryExitTypeCode,
                'device' => $result->ReaderName,
            ];
        });


    }

}
