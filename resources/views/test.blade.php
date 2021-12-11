 @extends('layouts.app', ['aktywneMenu' => "mojeKonto"])
 @push('js')
     <script src="{{ asset('js/libs/PomocnikLiczbowy.js') }}"></script>
     <script src="{{ asset('js/saldoPln.js') }}"></script>
 @endpush
 @section('content')
     <div id="saldoPln">
         @include('layouts.modals.potwierdzenieAkcji')
         @include('layouts.toasts.komunikat')
         @include('layouts.dolnyPasekNawigacji', ['aktywneDolneMenu' => "saldoPln"])
         <div class="container-fluid border-bottom">
         </div>
         <div class="container mt-3 mt-md-3 mt-xl-4">
             <div class="row" id="komunikat">
                 <div class="col-12">
                     @include('layouts.alert')
                 </div>
             </div>
             <div class="row mt-2 mt-md-2 mt-xl-3 mb-4 gx-4 gy-2">

                 <div class="col-6">
                     <div
                         class="row align-items-end g-0 border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                         <div class="col-12 align-self-start">
                             <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wpłata PLN</div>
                         </div>
                         <div class="col-12 align-self-start">
                             <p>asdasd</p>
                         </div>
                     </div>
                 </div>

                 <div class="col-6">
                    <div
                        class="row align-items-end g-0 border rounded-top border-start border-end border-bottom rounded-bottom h-100">
                        <div class="col-12 align-self-start">
                            <div class="niebieskie-tlo py-3 rounded-top text-center text-light">Wpłata PLN</div>
                        </div>
                        <div class="col-12">
                            <p>asdasd</p>
                        </div>
                        <div class="col-12">
                            <p>asdasd</p>
                        </div>
                        <div class="col-12">
                            <p>asdasd</p>
                        </div>
                    </div>
                </div>

             </div>
             @include('layouts.pasekPaginacji')
         </div>
     </div>
 @endsection
