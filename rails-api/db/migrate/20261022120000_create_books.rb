class CreateBooks < ActiveRecord::Migration[8.1]
  def change
    create_table :books do |t|
      t.string :title, null: false
      t.string :author, null: false
      t.string :isbn, null: false, index: { unique: true }
      t.integer :copies, null: false, default: 1
      t.timestamps

      t.check_constraint "copies >= 0", name: "books_copies_non_negative"
    end
  end
end
