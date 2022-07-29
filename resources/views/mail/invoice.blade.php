<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Email from OPER</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        {{-- <link rel="stylesheet" href="{{asset('css/invoice.css')}}"/> --}}
        <link href='http://fonts.googleapis.com/css?family=Roboto' rel='stylesheet' type='text/css'>
        <style>
            body {
                margin: 0;
                background-color: #cccccc;
            }
            table {
                border-spacing: 0;
            }
            td {
                padding: 0;
            }
            img {
                border: 0;
            }

            .email-bg{
                background: #f2f2f2;
                padding: 18px;
            }

            .logo{
                width: 120px;
            }

            .email-ctn, .pre-head {
                font-family: 'Roboto', sans-serif;
                font-size: 16px;
            }

            .pre-head{
                background: #f2f2f2;
                color: #f2f2f2;
                font-size: 5px;
            }

            .email-header{
                background: #f2f2f2;
                padding-top: 2%;
                padding-left: 5%;
                padding-right: 5%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }

            .email-title{
                color: #d50000;
            }

            .email-body{
                background: #f2f2f2;
                color: #666666;
                padding: 24px;
                padding-left: 5%;
                padding-right: 5%;
                display: flex;
                flex-direction: column;
                flex-wrap: wrap;
            }

            .username{
                color: #d50000
            }

            .content{
                margin-bottom: 20px;
            }

            .content-link{
                text-decoration: underline;
                color:#d50000;
            }

            .content-harga{
                text-align: center;
            }

            .content-card{
                background: white;
                border: solid 1px #d50000;
                border-radius: 26px;
                /* padding: 3%; */
            }

            .content-pt3{
                padding-top: 3%;
            }

            .rating{
                background: #d9d9d9;
                color: #666666;
                border-radius: 10px;
                padding: 3px;
                padding-right: 8px;
                padding-left: 8px;
                font-size: 12px;
                align-items:center;
                white-space: nowrap;
            }

            .card-header{
                background: #d50000;
                border: solid 1px #d50000;
                padding: 14px;
                padding-top: 3%;
                padding-left: 8%;
                padding-right: 8%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                border-top-left-radius: 20px;
                border-top-right-radius: 20px;
                border-bottom-left-radius: 50% 30px;
                border-bottom-right-radius: 50% 30px;
                height: 60px;
                margin-bottom: 3%;
            }

            .upper{
                transform: translateY(-12px);
            }

            .card-title{
                color: #f2f2f2;
                font-size: 16px;
            }

            .card-content{
                background: rgba(1,1,1,0);
                padding: 14px;
                padding-left: 8%;
                padding-right: 8%;
                display: flex;
                flex-direction: column;
                flex-wrap: wrap;
                justify-content:center;
                align-items: center;
            }

            .card-datauser{
                width: 100%;
            }

            .card-content-title{
                text-align:center;
                font-size: 16px;
                font-weight: bold;
            }

            .card-column{
                /* padding: 14px;
                padding-left: 24px;
                padding-right: 24px; */
                display: flex;
                flex-direction: row;
                /* flex-wrap: wrap; */
                justify-content: space-between;
                align-items:baseline;
            }

            .card-column-driver{
                align-items:center;
            }

            .card-column-divider{
                width: 50%;
                padding: 5%;
            }

            .pl-0{
                padding-left: 0;
            }
            .pr-0{
                padding-right: 0;
            }

            .card-data{
                font-size: 14px;
                width: 100%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: baseline;
            }

            .card-biaya{
                width: 100%;
            }

            .card-biaya-tag{
            }

            .mx-biaya{
                margin-left: 3%;
                margin-right: 3%;
            }

            .ml-0{
                margin-left: 0;
            }

            .mr-0{
                margin-right: 0;
            }

            .pt-0{
                padding-top: 0;
            }

            .icon{
                width:10px;
                height: auto;
            }

            .biaya-title{
                width: 100%;
                font-weight: bold;
                text-align: center;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items:center;
                margin-bottom: 2%;
            }

            .biaya-total{
                font-size: large;
                background-color: rgba(213,0,0,10%);
                color: #d50000;
                width: 100%;
                font-weight: bold;
                text-align: center;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items:center;
            }

            .biaya-value{
                width: 100%;
                text-align: center;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                align-items:baseline;
            }

            .biaya-table{
                padding-top: 5%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items:baseline;
                font-size: 14px;
            }

            .biaya-table-desc{
                width: 30%;
                text-align:left;
            }

            .biaya-table-desc-title{
                width: 30%;
                text-align:center;
            }

            .biaya-table-jmlh{
                width: 10%;
                text-align: center;
            }

            .biaya-table-harga{
                width: 30%;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
            }

            .biaya-total-harga{
                width: 30%;
                display: flex;
                flex-direction: row;
                justify-content: center;
            }

            .biaya-table-harga-title{
                width: 30%;
            }

            .biaya-table-subt{
                width: 30%;
                display: flex;
                flex-direction: row;
                justify-content: space-between;
                font-weight: bolder;
            }

            .biaya-table-subt-title{
                width: 30%;
            }

            .biaya-footer{

            }

            .data-title{
                font-weight: bold;
                width: 50%;
            }

            .data-value{
                text-align: right;
                align-items: flex-end;
                width: 50%;
            }

            .bg-f2{
                background: #f2f2f2;
            }

            .driver-ava{
                width: 30%;
                border: solid 2px #d50000;
                border-radius: 100%;
            }

            .driver-data{
                width: 60%;
            }

            .driver-id{
                color: #d50000;
                font-weight:bold;
                width: 100%;
                margin-top: 0;
            }

            .driver-name{
                color: #d50000;
                font-weight:bold;
                width: 100%;
                margin-bottom: 0;
                white-space: nowrap;
                font-size: 16px;
            }

            .card-driver-data{
                font-size: 14px;
                width: 100%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }

            .oper-divider {
                border-width: 1px 0px 0px 0px;
                border-color: #b2b2b2;
                border-style: solid;
                margin-top: 2%;
                margin-bottom: 2%;
                width: 100%;
            }


            .card-footer{
                margin-top: 5%;
                background: #f2f2f2;
                padding: 14px;
                padding-top: 3%;
                padding-left: 8%;
                padding-right: 8%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
                border-bottom-left-radius: 26px;
                border-bottom-right-radius: 26px;
            }

            .footer-socmed{
                width: 15%;
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: space-between;
                align-items: center;
            }

            .footer-text{
                font-size: 14px;
                font-weight: normal;
            }


            @media only screen and (max-width: 600px){

                .card-footer{
                    flex-direction: column-reverse;
                }

                .footer-socmed{
                    padding-top: 2%;
                    padding-bottom: 2%;
                    width: 30%;
                }

                .card-column{
                    flex-direction: column;
                }
                .card-column-divider{
                    width: 100%;
                    padding: 5%;
                }

                .pl-0{
                    padding: 0;
                }

                .pr-0{
                    padding: 0;
                }

                .card-content-title{
                    padding-bottom: 3%;
                }

                .card-biaya-tag{
                    padding-left: 5%;
                    padding-right: 5%;
                }

                .biaya-total{
                    font-size: 14px;
                }

                .upper{
                    transform: translateY(-8px);
                }
            }
        </style>
    </head>
    <body>
        {{-- {{ dd($mail) }} --}}
        {{-- {{
            dd([
                $order_ot,
                $order_b2c,
                $parsed_time_start,
                $parsed_time_end,
                $overtime,
                $elapsed_time,
                $paket_cost,
                $insurance_cost,
                $overtime_cost,
            ])
        }} --}}
        <div class="email-bg">
            {{-- <div class="pre-head">
                Ini adalah teks yang akan muncul di tampilan inbox penerimanya.
            </div> --}}
            <div class="email-ctn">
                <div class="email-header">
                    <div><img class="logo" src="{{ asset('invoice/logo-red.png') }}"/></div>
                    <div><h2 class="email-title">INVOICE</h2></div>
                </div>
                <div class="email-body">
                    <div class="email-body-text">
                        <div class="content content-head">
                            Dear, <b class="username">{{ $mail->order_b2c->customer->fullname }}</b>.<br/><br/>
                            Terima kasih telah memilih dan mempercayakan layanan OPER. Semoga Anda menikmati perjalanan bersama kami.
                        </div>
                        <div class="content-card">
                            <div class="card-header">
                                <div class="upper">
                                    <svg :width="60" height="23" viewBox="0 0 143 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M43.2494 16.4455C43.2494 17.9285 42.9913 19.2276 42.4813 20.3426C41.9683 21.4611 41.239 22.3176 40.287 22.9158C39.3381 23.514 38.2445 23.8149 37.0057 23.8149C35.7809 23.8149 34.6906 23.514 33.735 22.9158C32.7758 22.3176 32.0359 21.4646 31.5156 20.3603C30.9916 19.2523 30.7299 17.9746 30.7227 16.5304V15.6773C30.7227 14.2048 30.9813 12.9058 31.505 11.7767C32.0253 10.6475 32.7616 9.78741 33.7138 9.18921C34.6621 8.59098 35.7524 8.29008 36.9842 8.29008C38.2163 8.29008 39.3063 8.58738 40.2552 9.17864C41.2071 9.76964 41.9401 10.6192 42.4601 11.7306C42.9771 12.8421 43.2424 14.134 43.2494 15.6066V16.4455ZM40.6229 15.6596C40.6229 13.9854 40.3079 12.704 39.6743 11.8156C39.0446 10.9237 38.1454 10.4776 36.9842 10.4776C35.8518 10.4776 34.9632 10.9201 34.3262 11.8085C33.6856 12.6969 33.3598 13.9535 33.3458 15.5747V16.4455C33.3458 18.1055 33.6678 19.3869 34.3156 20.2895C34.9598 21.1956 35.8587 21.6487 37.0057 21.6487C38.1666 21.6487 39.0586 21.2063 39.6852 20.3214C40.3118 19.4364 40.6229 18.1444 40.6229 16.4455V15.6596" fill="#f2f2f2"/>
                                    <path d="M48.4842 18.0173V23.6028H45.8613V8.50277H51.638C53.3229 8.50277 54.6646 8.94167 55.6556 9.82302C56.6466 10.7045 57.1457 11.869 57.1457 13.3167C57.1457 14.7998 56.6572 15.9537 55.6874 16.7785C54.7176 17.6032 53.355 18.0173 51.6062 18.0173H48.4842ZM48.4842 15.8935H51.638C52.5724 15.8935 53.2841 15.674 53.776 15.2352C54.2645 14.7962 54.5122 14.1627 54.5122 13.3344C54.5122 12.5168 54.2609 11.8655 53.7654 11.3769C53.2662 10.892 52.5832 10.6407 51.7089 10.6265H48.4842V15.8935Z" fill="#f2f2f2"/>
                                    <path d="M68.2659 16.8704H62.0643V21.5003H69.3135V23.6028H59.4414V8.50277H69.2393V10.6265H62.0643V14.7892H68.2659V16.8704Z" fill="#f2f2f2"/>
                                    <path d="M76.996 17.805H74.0721V23.6028H71.4492V8.50277H76.7586C78.5001 8.50277 79.8451 8.89566 80.7904 9.67795C81.739 10.4601 82.2133 11.5929 82.2133 13.0725C82.2133 14.0847 81.9691 14.9308 81.4803 15.6139C80.9918 16.297 80.3124 16.8209 79.4417 17.189L82.8326 23.4683V23.6028H80.0223L76.996 17.805ZM74.0721 15.6812H76.7658C77.6508 15.6812 78.3444 15.4581 78.8399 15.0122C79.339 14.5662 79.5868 13.9573 79.5868 13.1857C79.5868 12.3787 79.3566 11.7521 78.8965 11.3097C78.4398 10.8673 77.7532 10.6407 76.84 10.6265H74.0721V15.6812Z" fill="#f2f2f2"/>
                                    <path d="M90.207 23.6028V8.50277H94.4687C95.782 8.50277 96.9429 8.79302 97.9554 9.37345C98.9642 9.95401 99.7426 10.7823 100.292 11.8513C100.844 12.9238 101.12 14.1556 101.127 15.5431V16.5095C101.127 17.9324 100.851 19.1819 100.302 20.2544C99.7535 21.3233 98.9676 22.1481 97.9481 22.7215C96.9289 23.2949 95.7432 23.5886 94.3873 23.6028H90.207ZM92.1999 10.1381V21.9675H94.2918C95.828 21.9675 97.021 21.4896 97.8775 20.5375C98.7307 19.5818 99.1554 18.2262 99.1554 16.4634V15.5821C99.1554 13.8653 98.7553 12.5344 97.9481 11.5858C97.1449 10.6336 96.0013 10.1522 94.522 10.1381H92.1999" fill="#f2f2f2"/>
                                    <path d="M109.255 14.0989C108.964 14.0494 108.649 14.0245 108.313 14.0245C107.053 14.0245 106.2 14.5591 105.75 15.6316V23.6028H103.832V12.3893H105.697L105.729 13.6813C106.359 12.6796 107.251 12.1769 108.405 12.1769C108.78 12.1769 109.064 12.2264 109.255 12.322V14.0989" fill="#f2f2f2"/>
                                    <path d="M113.11 23.6028H111.191V12.3893H113.11V23.6028ZM111.035 9.40539C111.035 9.09392 111.131 8.82842 111.318 8.61253C111.509 8.39653 111.793 8.29038 112.164 8.29038C112.54 8.29038 112.823 8.39653 113.014 8.61253C113.209 8.82842 113.304 9.09392 113.304 9.40539C113.304 9.71687 113.209 9.97887 113.014 10.1876C112.823 10.3965 112.54 10.4992 112.164 10.4992C111.793 10.4992 111.509 10.3965 111.318 10.1876C111.131 9.97887 111.035 9.71687 111.035 9.40539Z" fill="#f2f2f2"/>
                                    <path d="M119.875 21.0013L122.653 12.3893H124.615L120.59 23.6028H119.128L115.064 12.3893H117.022L119.875 21.0013" fill="#f2f2f2"/>
                                    <path d="M130.985 23.8149C129.466 23.8149 128.228 23.3159 127.272 22.3176C126.319 21.3159 125.842 19.9815 125.842 18.3072V17.9532C125.842 16.8418 126.054 15.8472 126.479 14.973C126.904 14.0986 127.498 13.412 128.263 12.9199C129.027 12.4244 129.856 12.1766 130.747 12.1766C132.206 12.1766 133.338 12.6579 134.149 13.6172C134.956 14.58 135.363 15.957 135.363 17.748V18.548H127.76C127.788 19.6524 128.111 20.5443 128.73 21.224C129.349 21.9036 130.135 22.2433 131.091 22.2433C131.767 22.2433 132.341 22.1053 132.811 21.8292C133.282 21.5531 133.693 21.1885 134.047 20.7355L135.218 21.6487C134.277 23.0928 132.864 23.8149 130.985 23.8149ZM130.747 13.7482C129.972 13.7482 129.325 14.0313 128.797 14.5977C128.274 15.164 127.948 15.957 127.824 16.9764H133.445V16.8311C133.388 15.8507 133.126 15.0933 132.655 14.5552C132.185 14.0172 131.548 13.7482 130.747 13.7482Z" fill="#f2f2f2"/>
                                    <path d="M142.999 14.0989C142.709 14.0494 142.394 14.0245 142.058 14.0245C140.797 14.0245 139.944 14.5591 139.495 15.6316V23.6028H137.576V12.3893H139.442L139.474 13.6813C140.104 12.6796 140.996 12.1769 142.15 12.1769C142.525 12.1769 142.808 12.2264 142.999 12.322V14.0989" fill="#f2f2f2"/>
                                    <path d="M13.5192 18.1122C17.6893 19.2681 20.7509 23.091 20.7509 27.6292H1C1 23.091 4.06135 19.2681 8.23174 18.1122L10.8754 22.6915L13.5192 18.1122Z" stroke="#f2f2f2" stroke-width="1.04624" stroke-miterlimit="22.9256" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M16.9193 7.04464C17.4363 5.99098 17.6734 4.9721 17.6693 3.98372C17.6614 2.16795 14.2149 1.35525 12.0612 1.08563C11.1505 0.97185 10.6111 0.971294 9.70061 1.08487C7.5473 1.35363 4.09383 2.1661 4.08599 3.98372C4.08183 4.97222 4.31898 5.99098 4.83602 7.04464M16.9193 7.04464H4.83602M16.9193 7.04464C17.0062 7.19318 17.0522 7.3473 17.0522 7.5051C17.0522 7.97907 16.6403 8.41849 15.9384 8.77935M4.83602 7.04464C4.74903 7.19318 4.70305 7.3473 4.70305 7.5051C4.70305 7.97843 5.11349 8.41718 5.81316 8.77748M15.9384 8.77935C14.822 9.35325 12.9714 9.72869 10.8776 9.72869C8.91571 9.72869 7.16739 9.39925 6.03658 8.88551C5.95919 8.8503 5.88444 8.81433 5.81316 8.77748M15.9384 8.77935C15.9432 8.79547 15.9951 8.94097 15.9996 8.9571C16.1352 9.42735 16.2083 9.92455 16.2083 10.4386C16.2083 13.3827 13.8217 15.7693 10.8776 15.7693C7.93355 15.7693 5.54702 13.3827 5.54702 10.4386C5.54702 9.95745 5.61089 9.49113 5.73033 9.04766C5.7421 9.00341 5.80007 8.82153 5.81316 8.77748M8.50377 4.35635H13.2517" stroke="#f2f2f2" stroke-width="1.04624" stroke-miterlimit="22.9256" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14.6031 24.4555H16.0171M4.42773 24.4555H8.44576H4.42773Z" stroke="#f2f2f2" stroke-width="1.04624" stroke-miterlimit="22.9256" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="upper"><h2 class="card-title">INVOICE</h2></div>
                            </div>

                            <!-- DATA PENGGUNA -->
                            <div class="card-content">
                                <div class="card-datauser">
                                    <div class="card-content-title">
                                        DATA PENGGUNA
                                    </div>
                                    <div class="card-column">
                                        <div class="card-column-divider pl-0">
                                            <div class="card-data">
                                                <p class="data-title">Nama</p>
                                                <p class="data-value">{{ $mail->order_b2c->customer->fullname }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">No. HP</p>
                                                <p class="data-value">+62 {{ $mail->order_b2c->customer->phone }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Email</p>
                                                <p class="data-value">{{ $mail->order_b2c->customer->email }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Gender</p>
                                                @if ($mail->order_b2c->customer->gender == 1)
                                                    <p class="data-value">Pria</p>
                                                @else
                                                    <p class="data-value">Wanita</p>
                                                @endif
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Merk Mobil</p>
                                                <p class="data-value">{{ $mail->order_ot->vehicle_branch->brand_name }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Tipe Mobil</p>
                                                <p class="data-value">{{ $mail->order_ot->vehicle_type }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">No. Polisi</p>
                                                <p class="data-value">{{ $mail->order_ot->client_vehicle_license }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Alamat</p>
                                                <p class="data-value">{{ $mail->order_ot->destination_name }}</p>
                                            </div>
                                        </div>

                                        <div class="card-column-divider pr-0">
                                            <div class="card-data">
                                                <p class="data-title">Kode Booking</p>
                                                <p class="data-value">{{ $mail->order_ot->trx_id }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Paket</p>
                                                @switch($mail->order_b2c->service_type_id)
                                                    @case(1)
                                                        <p class="data-value">12 Jam</p>
                                                        @break

                                                    @case(2)
                                                        <p class="data-value">4 Jam</p>
                                                        @break

                                                    @default
                                                        <p class="data-value">9 Jam</p>
                                                @endswitch
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Mulai</p>
                                                {{-- <p class="data-value">07.00 - 24 Juni 2022</p> --}}
                                                <p class="data-value">{{ $mail->parsed_time_start }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Berakhir</p>
                                                <p class="data-value">{{ $mail->parsed_time_end }}</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Jumlah Overtime</p>
                                                <p class="data-value">{{ $mail->overtime }} Jam</p>
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Asuransi</p>
                                                @if ($mail->order_b2c->insurance == 0)
                                                    <p class="data-value">Tidak</p>
                                                @else
                                                    <p class="data-value">Ya</p>
                                                @endif
                                            </div>
                                            <div class="card-data">
                                                <p class="data-title">Catatan</p>
                                                <p class="data-value">{{ $mail->order_b2c->notes }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DETAIL ORDER -->
                            <div class="card-content bg-f2">
                                <div class="card-datauser content-pt3">
                                    <div class="card-content-title">
                                        DETAIL ORDER
                                    </div>
                                    <div class="card-column card-column-driver">
                                        <div class="card-column-divider pl-0">
                                            <div class="card-driver-data">
                                                <img class="driver-ava" src="{{ asset('invoice/driver-ava.jpg') }}"/>
                                                <div class="driver-data">
                                                    <p class="driver-name">
                                                        {{ $mail->order_ot->driver->user->name }}
                                                        @if ($mail->rating > 0)
                                                            <span class="rating">
                                                                <img class="icon" src="{{ asset('invoice/star.svg') }}" />
                                                                {{ $mail->rating }}
                                                            </span>
                                                        @endif
                                                    </p>
                                                    <p class="driver-id">ID: DRV-OPR-{{ $mail->order_ot->driver->iddriver }}</p>
                                                    <p class="driver-value">{{ $mail->order_ot->driver->user->phonenumber }}</p>
                                                    <p class="driver-value">{{ $mail->order_ot->driver->address }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card-column-divider pr-0">
                                            <div class="card-data">
                                                <p class="data-title">Waktu Perjalanan</p>
                                                <p class="data-value">{{ $mail->elapsed_time }}</p>
                                            </div>
                                            {{-- <div class="card-data">
                                                <p class="data-title">Jarak Tempuh</p>
                                                <p class="data-value">12,3 Km</p>
                                            </div> --}}
                                            <div class="card-data">
                                                <p class="data-title">Metode Pembayaran</p>
                                                <p class="data-value">Transfer Bank</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BIAYA -->
                            <div class="card-content card-biaya-tag">
                                <div class="card-biaya content-pt3">
                                    <div class="card-content-title">
                                        BIAYA
                                    </div>
                                    <div class="biaya-table">
                                        <div class="biaya-title">
                                            <div class="mx-biaya ml-0 biaya-table-desc-title">Deskripsi</div>
                                            <div class="mx-biaya biaya-table-jmlh">Jumlah</div>
                                            <div class="mx-biaya biaya-table-harga-title">Harga</div>
                                            <div class="mx-biaya mr-0 biaya-table-subt-title">Subtotal</div>
                                        </div>

                                        <!-- FOR START-->

                                        <div class="biaya-value">
                                            @switch($mail->order_b2c->service_type_id)
                                                @case(1)
                                                    <div class="mx-biaya ml-0 biaya-table-desc">Paket 12 Jam</div>
                                                    @break

                                                @case(2)
                                                    <div class="mx-biaya ml-0 biaya-table-desc">Paket 4 Jam</div>
                                                    @break

                                                @default
                                                    <div class="mx-biaya ml-0 biaya-table-desc">Paket 9 Jam</div>
                                            @endswitch
                                            <div class="mx-biaya biaya-table-jmlh">1</div>
                                            <div class="mx-biaya biaya-table-harga">
                                                <p>Rp</p>
                                                <p>{{ $mail->paket_cost }}<span>,-</span></p>
                                            </div>
                                            <div class="mx-biaya mr-0 biaya-table-subt">
                                                <p>Rp</p>
                                                <p>{{ $mail->paket_cost }}<span>,-</span></p>
                                            </div>
                                        </div>

                                        @if ($mail->order_b2c->insurance == 1)
                                            <div class="biaya-value">
                                                <div class="mx-biaya ml-0 biaya-table-desc">Asuransi</div>
                                                <div class="mx-biaya biaya-table-jmlh">1</div>
                                                <div class="mx-biaya biaya-table-harga">
                                                    <p>Rp</p>
                                                    <p>{{ $mail->insurance_cost }}<span>,-</span></p>
                                                </div>
                                                <div class="mx-biaya mr-0 biaya-table-subt">
                                                    <p>Rp</p>
                                                    <p>{{ $mail->insurance_cost }}<span>,-</span></p>
                                                </div>
                                            </div>
                                        @endif

                                        @if ($mail->overtime > 0)
                                            <div class="biaya-value">
                                                <div class="mx-biaya ml-0 biaya-table-desc">Overtime</div>
                                                <div class="mx-biaya biaya-table-jmlh">{{ $mail->overtime }}</div>
                                                <div class="mx-biaya biaya-table-harga">
                                                    <p>Rp</p>
                                                    <p>35.000<span>,-</span></p>
                                                </div>
                                                <div class="mx-biaya mr-0 biaya-table-subt">
                                                    <p>Rp</p>
                                                    <p>{{ $mail->overtime_cost }}<span>,-</span></p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="oper-divider"></div>
                                    <div class="biaya-total pt-0">
                                        <div class="mx-biaya ml-0 biaya-table-desc"></div>
                                        <div class="mx-biaya biaya-table-jmlh"></div>
                                        <div class="mx-biaya biaya-total-harga">
                                            TOTAL
                                        </div>
                                        <div class="mx-biaya mr-0 biaya-table-subt">
                                            <p>Rp</p>
                                            <p>{{ $mail->overall_cost }}<span>,-</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-footer">
                                <div class="footer-socmed">
                                    <img class="footer-icon" src="{{ asset('invoice/fb-icon.svg') }}"/>
                                    <img class="footer-icon" src="{{ asset('invoice/ig-icon.svg') }}"/>
                                    <img class="footer-icon" src="{{ asset('invoice/in-icon.svg') }}"/>
                                </div>
                                <div>
                                    <h2 class="footer-text">Lorem ipsum sit dolor amet? <a class="content-link">Hubungi Kami</a></h2>
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        </div>

    </body>
</html>
