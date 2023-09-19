<?php

class Controller_Form extends Controller_Template
{
    public function action_index()
    {
        $this->template->title = 'コンタクトフォーム';
        $this->template->content = View::forge('form/index');
    }
    public function forge_validation()
    {
        $val = Validation::forge();
		
		$val->add('name', '名前')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('max_length', 50);
		
        $val->add('email', 'メールアドレス')
			->add_rule('trim')
			->add_rule('required')
			->add_rule('max_length', 100)
			->add_rule('valid_email');
		
        $val->add('comment', 'コメント', )
			->add_rule('required')
			->add_rule('max_length', 400);
		
		return $val;
    }
    public function action_confirm()
	{
		$val = $this->forge_validation();		
		if ($val->run())
		{
			$data['input'] = $val->validated();
			$this->template->title = 'コンタクトフォーム: 確認';
			$this->template->content = View::forge('form/confirm', $data);
		}
		else
		{
			$this->template->title = 'コンタクトフォーム: エラー';
			$this->template->content = View::forge('form/index');
			$this->template->content->set_safe('html_error', $val->show_errors());
		}
	}
	public function action_send()
	{
		// CSRF対策
		if ( ! Security::check_token())
		{
			throw new HttpInvalidInputException('ページ遷移が正しくありません');
		}
		
		$val = $this->forge_validation();
		
		if ( ! $val->run())
		{
			$this->template->title = 'コンタクトフォーム: エラー';
			$this->template->content = View::forge('form/index');
			$this->template->content->set_safe('html_error', $val->show_errors());
			return;
		}
		
		$post = $val->validated();
		$date = $this->build_mail($post);
		
		// メールの送信
		try
		{
			$mail->send($post);
			$this->template->title = 'コンタクトフォーム: 送信完了';
			$this->template->content = View::forge('form/send');
			return;
		}
		catch (EmailValidationFailedException $e)
		{
			Log::error(
				'メール検証エラー: ' . $e->getMessage(), __METHOD__
			);
			$html_error = '<p>メールアドレスに誤りがあります。</p>';
		}
		catch (EmailSendingFailedException $e)
		{
			Log::error(
				'メール送信エラー: ' . $e->getMessage(), __METHOD__
			);
			$html_error = '<p>メールを送信できませんでした。</p>';
		}
		
		$this->template->title = 'コンタクトフォーム: 送信エラー';
		$this->template->content = View::forge('form/index');
		$this->template->content->set_safe('html_error', $html_error);
	}
}