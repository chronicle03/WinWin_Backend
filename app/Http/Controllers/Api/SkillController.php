<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Helpers\ResponseFormatter;
use Illuminate\Http\Request;
use App\Models\Skill;

class SkillController extends Controller
{
    public function getSkills(){

        try {
            $skill = Skill::select('name')->get();
            return ResponseFormatter::success([
                'skills' => $skill
            ], 'Success get all skills');
        } catch (\Exception $e) {
            return ResponseFormatter::error([
                'message' => 'something error',
                'error' => 'something error',
            ], 'something error', 500);
        }
    }

    public function create(Request $request){

        try {

            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return ResponseFormatter::error([
                    'message' => 'Bad Request',
                    'errors' => $validator->errors()
                ], 'Bad Request', 400);
            }
            Skill::create(['name'=> $request->name]);

            $skill = Skill::select('name')->where('name', $request->name)->first();
          
            return ResponseFormatter::success([
                'skills' => $skill
            ], 'Success create skill');
        } catch (\Exception $e) {
            return ResponseFormatter::error([
                'message' => 'something error',
                'error' => 'something error',
            ], 'something error', 500);
        }
    }
}

