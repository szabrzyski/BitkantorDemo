<div class="modal fade" id="formularzRealizacjiWyplatyPln" ref="formularzRealizacjiWyplatyPln" tabindex="-1"
    aria-labelledby="formularzRealizacjiWyplatyPlnLabela" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formularzRealizacjiWyplatyPlnLabela">Realizacja wypłaty PLN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form class="mb-0" method="POST" v-on:submit="walidujOrazWyslijFormularz">
                @csrf
                <div class="modal-body">
                    <label for="tytulPrzelewuWyplatyPln" class="mb-2">Tytuł przelewu</label>
                    <input type="text" minlength="1" maxlength="255" pattern="[A-Za-z0-9]+"
                        class="form-control lekko-niebieskie-tlo" name="tytulPrzelewuWyplatyPln"
                        v-model="tytulPrzelewuWyplatyPln" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Zatwierdź
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
