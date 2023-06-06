<?php
/**
 * 评论接口
 * @author xudt
 * @date   : 2020/3/20 13:10
 */
namespace frontend\services\comment\interfaces;

interface CommentInterface
{
    public function comment(); //评论

    public function reply(); //回复

    public function getCommentList(); //获取评论列表

    public function getReplyList();  //获取回复列表

}