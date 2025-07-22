<div class="row g-4">
    <div class="col-12">
        <div class="row">
            <div class="col-12 col-sm-12 col-md-6 form-group">
                <label for="f-name">Nombre</label>
                <input type="text" class="form-control" id="f-name" name="name" value="{{ $league->name ?? '' }}">
            </div>
            <div class="col-12 col-sm-12 col-md-6 form-group">
                <label for="f-biwenger_id">Biwenger ID</label>
                <input type="text" class="form-control" id="f-biwenger_id" name="biwenger_id" value="{{ $league->biwenger_id ?? '' }}">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-sm-12 col-md-6 form-group">
                <label for="f-bearer_user">Bear User</label>
                <input type="text" class="form-control" id="f-bearer_user" name="bearer_user" value="{{ $league->bearer_user ?? '' }}">
            </div>
            <div class="col-12 col-sm-12 col-md-6 form-group">
                <label for="f-bearer_league">Bearer League</label>
                <input type="text" class="form-control" id="f-bearer_league" name="bearer_league" value="{{ $league->bearer_league ?? '' }}">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-sm-12 col-md-12 form-group">
                <label for="f-bearer_token">Bearer Token</label>
                <textarea class="form-control" id="f-bearer_token" name="bearer_token">{{ $league->bearer_token ?? '' }}</textarea>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 col-sm-12 col-md-6 form-group">
                <button type="submit" class="btn btn-outline-primary me-3">Guardar</button>
            </div>
        </div>
    </div>
</div>