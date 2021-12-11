<div class="modal fade" id="formularzRealizacjiWyplatyBtc" ref="formularzRealizacjiWyplatyBtc" tabindex="-1"
    aria-labelledby="formularzRealizacjiWyplatyBtcLabela" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formularzRealizacjiWyplatyBtcLabela">Realizacja wypłaty BTC</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="mb-0" v-bind:action="linkRealizacjiWyplatyBtc" method="POST"
                v-on:submit="walidujOrazWyslijFormularz">
                @csrf
                <div class="modal-body">
                    <label for="txidRealizowanejWyplatyBtc" class="mb-2">TxID</label>
                    <input type="text" minlength="1" maxlength="255" pattern="[A-Za-z0-9]+" class="form-control lekko-niebieskie-tlo" name="txidRealizowanejWyplatyBtc"
                        v-model="txidRealizowanejWyplatyBtc" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100" v-bind:disabled='trwaRealizacjaWyplatyBtc'>Zatwierdź
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
