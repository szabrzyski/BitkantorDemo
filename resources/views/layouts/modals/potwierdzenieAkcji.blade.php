<div class="modal fade" id="potwierdzenieAkcji" ref="potwierdzenieAkcji" tabindex="-1"
    aria-labelledby="potwierdzenieAkcjiLabela" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="potwierdzenieAkcjiLabela">Potwierdź akcję</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @{{ tekstPotwierdzeniaAkcji }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Nie</button>
                <button type="button" class="btn btn-success" data-bs-dismiss="modal"
                    v-on:click="wywolajFunkcje(nazwaWywolywanejFunkcji, parametrWywolywanejFunkcji)">Tak</button>
            </div>
        </div>
    </div>
</div>
