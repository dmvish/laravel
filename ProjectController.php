<?php

namespace App\Http\Controllers\App;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Validator;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $allowedOrders = ['desc', 'asc'];
        $orderBy = (in_array($request->input('order_by'), $allowedOrders)) ? $request->input('order_by') : 'desc';

        $allowedSorts = array_keys(__('dbcolumns.projects'));
        $sortBy = (in_array($request->input('sort_by'), $allowedSorts)) ? $request->input('sort_by') : 'created_at';

        $limit = (int) $request->input('limit') ?: 15;

        $projects = Project::where('user_id', Auth::id())
            ->orderBy($sortBy, $orderBy)
            ->paginate($limit);

        $queryParams = Input::except('page');

        return view('app.projects.index', compact('projects', 'queryParams'));
    }

    /**
     * Displays the confirmation form of the resource deletion.
     *
     * @return \Illuminate\Http\Response
     */
    public function deleteConfirmation(Project $project)
    {
        return view('app.projects.confirmation.delete', compact('project'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if( ! Auth::user()->hasMultiProjectAccount())
        {
            Session::flash('responseAddMessages', [
                'warning' => __('projects.only_one_project_available')
            ]);

            return redirect()->route('projects.index');
        }

        $request->request->add(['user_id' => Auth::id()]);

        $projectData = $request->all();

        $validator = Validator::make($projectData, [
            'title' => 'required|max:255',
            'url' => 'sometimes|url'
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('projects.index')
                ->withErrors($validator,'store')
                ->withInput();
        }

        Project::create($projectData);

        Session::flash('responseAddMessages', [
            'success' => __('projects.successful_added')
        ]);

        return redirect()->route('projects.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        if(Auth::user()->canDeleteProject($project->id))
        {
            $project->delete($project->id);

            Session::flash('responsePageMessages', [
                'success' => __('projects.successful_deleted')
            ]);
        }else{
            Session::flash('responsePageMessages', [
                'warning' => __('common.action_not_available')
            ]);
        }

        return redirect()->route('projects.index');
    }
}
