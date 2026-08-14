<?php 
$count = $_GET['count'] + 1;
?>
<div class="row taskgroup">
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Task Name:</label>  
                            <input type="text" class="form-control" name="Duration[task][{{$count}}][name_of_task]"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Priority:</label>
                            <select class="select2 form-select" name="Duration[task][{{$count}}][priority]" aria-label="Default select example">
                                <option selected>Choose Priority</option>
                                <option value="high">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>  
                        </div>  
                    </div>
                    <div class="col-md-4" > 
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="start_date">Start Date:</label>  
                            <input type="date" class="form-control" name="Duration[task][{{$count}}][start_date]"/>  
                        </div>
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Description </label>
                             <input type="text"  class="form-control" name="Duration[task][{{$count}}][description]" />  
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="first_name">Status:</label>
                            <select class="select2 form-select"  name="Duration[task][{{$count}}][status]"  aria-label="Default select example">
                                <option selected>Choose Status</option>
                                <option value="pending">Pending</option>
                                <option value="inprogress">Inprogress</option>
                                <option value="completed">Completed</option>
                            </select>  
                        </div>  
                        <div class="form-group" style=" margin-top: 20px;">      
                            <label for="end_date">End Date:</label>  
                            <input type="date" class="form-control" name="Duration[task][{{$count}}][end_date]"/>
                        </div>
                    </div>
                </div>