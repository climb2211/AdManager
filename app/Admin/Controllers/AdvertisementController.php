<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\AdvertisementRepository;
use App\Admin\Repositories\ChannelRepository;
use App\Admin\Requests\StoreAdvertisementRequest;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;

class AdvertisementController extends Controller
{
    use ModelForm;

    protected $advertisementRepository;
    protected $channelRepository;

    public function __construct(AdvertisementRepository $advertisementRepository,ChannelRepository $channelRepository)
    {
        $this->advertisementRepository = $advertisementRepository;
        $this->channelRepository = $channelRepository;
    }

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('广告列表');
            $content->description('');

            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {
            $content->header('编辑广告');
            $content->description('');
            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {
            $content->header('新增广告');
            $content->description('');
            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid($this->advertisementRepository->getSelfModelClassName(), function (Grid $grid) {
            $grid->model()->where('is_delete', 'F');
            $grid->id('ID')->sortable();
            $grid->column('name','广告名称');
            $grid->column('uuid','推广ID');
            $grid->column('loading_page','广告跟踪类型');
            $grid->column('track_type','落地页');
            $grid->channels('投放渠道')->display(function ($channels) {
                $channels = array_map(function ($channel) {
                    return "<span class='label label-success'>{$channel['name']}</span>";
                }, $channels);
                return join('&nbsp;', $channels);
            });
            $grid->created_at('创建时间');
            $grid->updated_at('更改时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form($this->advertisementRepository->getSelfModelClassName(), function (Form $form) {
            $form->display('id', 'ID');
            // 添加提交验证规则
            $form->text('name', '广告标题');
            $form->select('track_type', '广告跟踪类型')->options([
                'talking_data' => 'talking data'
            ]);
            $form->text('loading_page', '落地页');
            $form->text('click_track_url', '广告上报地址');
            $form->multipleSelect('channels','投放渠道')->options($this->channelRepository->getAllDataPluckNameAndId());
            $form->display('created_at', 'Created At');
            $form->display('updated_at', 'Updated At');
        });
    }

    public function store(StoreAdvertisementRequest $request)
    {
        $data = [
            'name' => $request->get('name'),
            'track_type' => $request->get('track_type'),
            'loading_page'  => $request->get('loading_page'),
            'click_track_url' => $request->get('click_track_url'),
            'add_user_id' => Admin::user()->id,
            'update_user_id' => Admin::user()->id,
            'uuid' => Uuid::generate(),
        ];

        $channels = $request->get('channels');

        $advertisement = $this->advertisementRepository->create($data);

        if(count($channels) > 0){
            $advertisement->channels()->attach(array_filter($channels));
        }

        return redirect(route('advertisements.index'));
    }

    public function update(StoreAdvertisementRequest $request,$id)
    {
        $advertisement = $this->advertisementRepository->byId($id);

        if($advertisement){
            $data = [
                'name' => $request->get('name'),
                'track_type' => $request->get('track_type'),
                'loading_page'  => $request->get('loading_page'),
                'click_track_url' => $request->get('click_track_url'),
                'update_user_id' => Admin::user()->id,
            ];
            $advertisement->update($data);
            $channels = array_filter($request->get('channels'));
            $advertisement->channels()->sync($channels);
            return redirect(route('advertisements.index'));
        }
        return back();
    }

    public function destroy(Request $request,$ids)
    {
        $arrId = explode(',',$ids);
        $data = [
            'is_delete' => 'T',
            'update_user_id' => Admin::user()->id,
        ];
        $flag = $this->advertisementRepository->batchDelete($arrId,$data);
        if($flag){
            return [
                'status' => 1,
                'message' => '删除成功'
            ];
        }else{
            return [
                'status' => 0,
                'message' => '删除失败'
            ];
        }
    }
}