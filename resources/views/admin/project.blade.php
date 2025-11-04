<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Add Project </title>
   <!-- plugins:css -->
    @include('admin.css')

 
</head>
<body>
    @include('admin.header')
    <!-- partial:partials/_sidebar.html -->
    @include('admin.sidebar')
    <!-- partial -->
    @include('admin.navbar')
        
    <div class="main-panel">
        <div class="content-wrapper">
            <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card bg-white border border-dark shadow">
                        <div class="card-body">
                            <h4 class="card-header mb-4 text-black">Add Project</h4>
                            <a href="{{ url('manage_projects') }}" class="btn btn-outline-secondary mb-3">
                                Manage Projects
                            </a>
                            @if(session('message'))
                                <div class="alert alert-success">
                                    {{ session('message') }}
                                </div> 
                            @endif
                            <form action="{{url('upload_project')}}" method="POST" enctype="multipart/form-data">

                                @csrf

                                <div class="form-group">
                                    <label for="name">Project Name</label>
                                    <input type="text" class="form-control" name="name" style="background-color: white; color: black;" required>
                                </div>

                                <div class="form-group">
                                    <label for="country_id">Select Country</label>
                                    <select name="country_id" class="form-control" style="background-color: white; color: black;"required>
                                        <option value="">-- Select Country --</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->id }}">{{ $country->country_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="description">Project Description</label>
                                    <textarea class="form-control" name="description" rows="3" style="background-color: white; color: black;"></textarea>
                                </div>

                                <div id="participant-section">
                                    <label>Participants</label>
                                    <div class="form-group d-flex mb-2">
                                        <input type="text" name="participants[0][name]" placeholder="Name" class="form-control mr-2" style="background-color: white; color: black;" required>
                                        <input type="text" name="participants[0][contact]" placeholder="Contact" class="form-control" style="background-color: white; color: black;" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="start_date">Date of Commencement</label>
                                    <input type="date" class="form-control" name="start_date" style="background-color: white; color: black;"required>
                                </div>

                                <div class="form-group">
                                    <label for="duration_value">Duration</label>
                                    <div class="d-flex">
                                        <input type="number" name="duration_value" min="1" class="form-control mr-2"  style="background-color: white; color: black;" required>
                                        <select name="duration_unit" class="form-control" style="background-color: white; color: black;" required>
                                            <option value="">Select Unit</option>
                                            <option value="days">Days</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                </div>


                                <button type="button" class="btn btn-sm btn-outline-primary mb-3" onclick="addParticipant()">+ Add Participant</button>

                                <button type="submit" class="btn btn-primary">Add Project</button>
                                <a href="#" class="btn btn-danger">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div> <!-- content-wrapper -->       
        </div>
    </div>

    <!-- partial -->

    <!-- container-scroller -->
    <!-- plugins:js -->
     <script>
let index = 1;
function addParticipant() {
    const section = document.getElementById('participant-section');
    const div = document.createElement('div');
    div.classList.add('form-group', 'd-flex', 'mb-2');
    div.innerHTML = `
        <input type="text" name="participants[${index}][name]" placeholder="Name" class="form-control mr-2" style="background-color: white; color: black;" required>
        <input type="text" name="participants[${index}][contact]" placeholder="Contact" class="form-control" style="background-color: white; color: black;" required>
    `;
    section.appendChild(div);
    index++;
}
</script>

    @include('admin.java')
    <!-- End custom js for this page -->
</body>
</html>
