<table>
    <thead>
    <tr bgcolor="#FF0000">
        <th colspan="2" align="center">Bulk Order Template Guide</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td>Template ID</td>
            <td>Isi sesuai dengan informasi ID yang tertera dibawah ini</td>
        </tr>
        <tr>
            <td>Date booking</td>
            <td>Isi kolom Date booking dengan format waktu YYYY-MM-DD</td>
        </tr>
        <tr>
            <td>Time booking</td>
            <td>Isi Kolom Time Booking dengan format waktu Jam:Menit:Detik</td>
        </tr>
        <tr>
            <td>Message</td>
            <td></td>
        </tr>
        <tr>
            <td>User name</td>
            <td></td>
        </tr>
        <tr>
            <td>User phone number</td>
            <td></td>
        </tr>
        <tr>
            <td>ID Origin location</td>
            <td></td>
        </tr>
        <tr>
            <td>ID Destination location</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle brand</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle Type</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle Transmission</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle License</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle Owner</td>
            <td></td>
        </tr>
        <tr>
            <td>Vehicle years</td>
            <td></td>
        </tr>
    </tbody>
</table>

<br><br>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Location Name</th>
        <th>Location Detail Longitude Latitude</th>
    </tr>
    </thead>
    <tbody>
    @foreach($places as $place)
        <tr>
            <td>{{ $place->idplaces }}</td>
            <td>{{ $place->name }}</td>
            <td>{{ $place->latitude }} - {{ $place->longitude }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br><br>
<table>
    <thead>
    <tr>
        <th>ID Template</th>
        <th>Template Name</th>
        <th>Detail Template Task</th>
    </tr>
    </thead>
    <tbody>
    @foreach($tasktemplate as $template)
        <tr>
            <td>{{ $template->task_template_id }}</td>
            <td>{{ $template->task_template_name }}</td>
            <td></td>
        </tr>
            @foreach ($template->team as $post)
            <tr>
                <td></td>
                <td>{{ $post['name'] }}</td>
                <td>{{ $post['description'] }}</td>
            </tr>
            @endforeach
        
    @endforeach
    </tbody>
</table>

<br><br>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Brand</th>
    </tr>
    </thead>
    <tbody>
    @foreach($brands as $brand)
        <tr>
            <td>{{ $brand->id }}</td>
            <td>{{ $brand->brand_name }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<br><br>
<table>
    <thead>
    <tr>
        <th>ID</th>
        <th>Transmission</th>
    </tr>
    </thead>
    <tbody>
        <tr>
            <td>M</td>
            <td>Manual</td>
        </tr>
        <tr>
            <td>AT</td>
            <td>Automated</td>
        </tr>
    </tbody>
</table>