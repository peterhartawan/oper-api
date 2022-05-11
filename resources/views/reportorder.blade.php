<table>
    <thead>
        <tr>
            <th align="center">ID Order</th>
            <th align="center">Driver Name</th>
            <th align="center">Dispatcher Name</th>
            <th align="center">Booking Time</th>
            <th align="center">Origin Latitude</th>
            <th align="center">Origin Longitude</th>
            <th align="center">Destination Latitude</th>
            <th align="center">Destination Longitude</th>
            <th align="center">Client Vehicle License</th>
            <th align="center">Vehicle Brand</th>
            <th align="center">Vehicle Type</th>
            <th align="center">Vehicle Transmission</th>
            <th align="center">Vehicle Year</th>
            <th align="center">Message</th>
            <th align="center">Order Number</th>
            <th align="center">Order Type</th>
            <th align="center">Order Status</th>
            <th></th>
            <th align="center">Task Descriptions</th>
            <th align="center">Task Images</th>
    </tr>
    </thead>
    <tbody>
        @foreach ($orders as $order)
            <tr>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["idorder"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["driver_name"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["dispatcher_name"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["booking_time"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["origin_latitude"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["origin_longitude"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["destination_latitude"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["destination_longitude"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["client_vehicle_license"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["vehicle_brand_id"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["vehicle_type"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["vehicle_transmission"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["vehicle_year"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["message"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["trx_id"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["order_type_name"] }}</td>
                <td rowspan="{{count($order["tasks"])}}">{{ $order["status_text"] }}</td>
                <td>1</td>
                <td>{{ $order["tasks"][0]["description"] }}</td>
                <td>{{ str_replace("public", "https://rest.oper.co.id/storage", $order["tasks"][0]["attachment_url"]) }}</td>
            </tr>
            @for ($i = 1; $i < count($order["tasks"]); $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $order["tasks"][$i]["description"] }}</td>
                    <td>{{ str_replace("public", "https://rest.oper.co.id/storage", $order["tasks"][$i]["attachment_url"]) }}</td>
                </tr>
            @endfor
        @endforeach
    </tbody>
</table>
