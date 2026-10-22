class ApplicationController < ActionController::API
  rescue_from ActiveRecord::RecordNotFound do |error|
    render_error :not_found, "#{error.model || "Record"} not found"
  end

  rescue_from ActionController::ParameterMissing do |error|
    render_error :bad_request, error.message
  end

  private

  # Every error has the same shape: { "error": { "status", "message", "details"? } }
  def render_error(status, message, details = nil)
    render json: { error: { status: Rack::Utils.status_code(status), message:, details: }.compact }, status:
  end

  def render_invalid(record)
    render_error :unprocessable_content, "Validation failed", record.errors.to_hash
  end

  def page_params
    limit = Integer(params.fetch(:limit, 25), exception: false)&.clamp(1, 100) || 25
    offset = [ Integer(params.fetch(:offset, 0), exception: false) || 0, 0 ].max
    [ limit, offset ]
  end
end
