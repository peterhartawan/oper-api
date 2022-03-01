<table>
    <thead>
        <tr>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th align="center"></th>
            <th colspan={{count($tasks)}} align="center">Timestamp</th>
            <th colspan={{count($tasks)}} align="center">Response Time</th>
        </tr>
        <tr>
            <th align="center">Date</th>
            <th align="center">Time</th>
            <th align="center">Vehicle No</th>
            <th align="center">Vehicle Brand</th>
            <th align="center">Vehicle Type</th>
            <th align="center">Vehicle Year</th>
            <th align="center">Origin SiteName</th>
            <th align="center">Destination SiteName</th>
            <th align="center">Route</th>
            <th align="center">Administrator Name</th>
            <th align="center">Driver Name</th>
            <th align="center">Pickup Request Time</th>
            @foreach ($tasks as $template_task)
                <th align="center">{{ $template_task['name'] }}</th>
            @endforeach

            @foreach ($tasks as $template_task)
                <th align="center">{{ $template_task['name'] }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>

            @foreach ($detailorder as $order)
                <tr>
                    <td >{{ $order['date'] }}</td>
                    <td >{{ $order['time'] }}</td>
                    <td >{{ $order['client_vehicle_license'] }}</td>
                    <td >{{ $order['brand_name'] }}</td>
                    <td >{{ $order['vehicle_type'] }}</td>
                    <td >{{ $order['vehicle_year'] }}</td>
                    <td >{{ $order['origin_name'] }}</td>
                    <td >{{ $order['destination_name'] }}</td>
                    <td >{{ $order['route'] }}</td>
                    <td >{{ $order['name_dispatcher'] }}</td>
                    <td >{{ $order['name_driver'] }}</td>
                    <td >{{ $order['dispatch_time'] }}</td>

                    {{-- @foreach ($order->template as $post) --}}
                    @foreach ($order['template'] as $post)
						<td>{{ date('H:i:s', strtotime($post['date'])) }}</td>
                    @endforeach

                    <?php
                        $i = 0;
                        $pengurang = $order['dispatch_at'] ;
                    ?>
                    {{-- @foreach ($order->template as $post) --}}
                    @foreach ($order['template'] as $post)
                        <?php
                            $start = new DateTime($pengurang);
                            $end   = new DateTime($post['date']);
                            $interval = $start->diff($end);
                            $elapsed = $interval->format("%H h, %I m, %S s");
                            // $elapsed = $interval->format('%a days, %h hours,  %i minutes,  %s seconds');
                        ?>

                        <td >{{ $elapsed }}</td>
                        <?php
                            $i++;
                            $pengurang= $post['date'];
                        ?>
                    @endforeach
                </tr>
            @endforeach
    </tbody>
</table>
